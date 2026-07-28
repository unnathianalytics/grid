<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Resort;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use LaraGrid\Aggregate;
use LaraGrid\ColumnGroup;
use LaraGrid\Columns\CheckboxColumn;
use LaraGrid\Columns\DateColumn;
use LaraGrid\Columns\DecimalColumn;
use LaraGrid\Columns\FormulaColumn;
use LaraGrid\Columns\HiddenColumn;
use LaraGrid\Columns\IntegerColumn;
use LaraGrid\Columns\ReadonlyColumn;
use LaraGrid\Columns\SearchSelectColumn;
use LaraGrid\Columns\SelectColumn;
use LaraGrid\Columns\SerialColumn;
use LaraGrid\Columns\TextColumn;
use LaraGrid\Columns\YesNoColumn;
use LaraGrid\Editing\RowContext;
use LaraGrid\Grid;
use LaraGrid\GridDensity;
use LaraGrid\Livewire\WithLaraGrid;
use LaraGrid\SyncPolicy;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The EDITABLE showcase — a booking-lines entry screen over the real resorts table, exercising
 * the whole editing machinery in one definition.
 *
 * Covered here:
 *   modes/rows  — editable(), rowsFrom(), defaultRows(), newRowUsing(), minRows(), padRows(),
 *                 autoAppend(), sync(SyncPolicy::PerCell), gridMountRows()/gridRows()/reseedGrid()
 *   columns     — Serial, SearchSelect (async options + meta + onSelect enrichment +
 *                 endOfListOption), Select, Text (maxLength/upper), Integer, Decimal, Date,
 *                 Checkbox, YesNo, Formula (chained: base → tax → amount), Readonly and a
 *                 writable Hidden carried id
 *   rules       — rules(), required(), required(fn), readonly(fn), requiredWhen(), lockedWhen(),
 *                 whenFilled() sibling mirrors
 *   hooks       — onSelect(), afterCellChange() (derived nights), afterRowRemove()
 *   flow        — focusOnMount(), focusOutTo(), onCompleteFocus() + lgrid:complete,
 *                 opensPanel() + lgrid:panel / gridPanelDone(), refreshesHost()
 *   layout      — columnGroups(), stickyHeader(), freezeColumns(), density(), theme(),
 *                 statusBar(), persistWidths(), rowClass(), cellClass(), footer aggregates
 *
 * Nothing writes to the database — Save captures the cleaned rows and shows them.
 */
#[Layout('components.layouts.app', ['wide' => true])]
class BookingEntry extends Component
{
    use WithLaraGrid;

    private const PLANS = [
        'EP' => 'Room Only (EP)',
        'CP' => 'With Breakfast (CP)',
        'MAP' => 'Half Board (MAP)',
        'AP' => 'Full Board (AP)',
    ];

    private const TAX_STATUS = ['taxable' => 'Taxable', 'exempt' => 'Exempt'];

    /** @var list<array<string, mixed>> The grid-bound rows (each carries a stable _k). */
    public array $lines = [];

    /** @var list<array<string, mixed>> The last saved (cleaned) payload, for display. */
    public array $saved = [];

    /**
     * Host-owned extras captured by the ->opensPanel() modal, keyed by the grid's row key.
     * Deliberately NOT grid columns: a panel exists precisely for the data that belongs to a
     * line but has no place in the row of cells (serial numbers, long descriptions, in this
     * demo the guest's special requests).
     *
     * @var array<string, array{requests: string, arrival: string}>
     */
    public array $extras = [];

    /** The row key the open panel is editing, or null when no panel is open. */
    public ?string $panelRow = null;

    public string $panelRequests = '';

    public string $panelArrival = '';

    /** A plain host field, so ->focusOutTo() has somewhere meaningful to send Tab. */
    public string $remarks = '';

    public function mount(): void
    {
        $this->lines = $this->gridMountRows('lines');
    }

    /**
     * @return array<string, Grid>
     */
    protected function grids(): array
    {
        return [
            'lines' => Grid::make('lines')
                ->editable()
                ->rowsFrom('lines')
                // Demo app has no auth — permit openly. Gate with a policy in real apps.
                ->authorize(fn (): bool => true)
                ->defaultRows(3)
                // The new-row TEMPLATE: every declared column null, overlaid with these. The same
                // template builds seeded rows, Insert rows, auto-appended rows and paste rows — so
                // a row nobody touched still counts as BLANK (factory defaults are not operator
                // data) and stays exempt from validation, totals and gridRows().
                ->newRowUsing(fn (): array => [
                    'guests' => 2,
                    'plan' => 'CP',
                    'taxStatus' => 'taxable',
                    'gstPct' => '12.00',
                ])
                ->minRows(1)
                ->autoAppend()          // Enter past the last cell grows the grid
                ->padRows(3)            // paint empty filler rows so the box never looks cropped
                // One op per committed cell — the default. PerRow batches a row's ops until the
                // cursor leaves it; Deferred holds everything until the host flushes.
                ->sync(SyncPolicy::PerCell)
                ->keymap('entry')       // serpentine Enter flow; 'excel' = Enter down, Tab right
                ->focusOnMount()
                ->focusOutTo('#remarks')        // Tab off the last cell → the Remarks field
                ->onCompleteFocus('[data-save]')// the completion signal → Save
                // Re-render this component's own chrome (the live badge above the grid) whenever
                // one of these columns changes — the grid's rows are already written back to
                // $this->lines server-side, so render() can total them.
                ->refreshesHost(['nights', 'rate', 'gstPct', 'taxStatus', 'complimentary'])
                ->columnGroups([
                    ColumnGroup::make('Stay', ['fromDate', 'toDate', 'nights', 'guests']),
                    ColumnGroup::make('Tariff', ['plan', 'rate', 'complimentary', 'base']),
                    ColumnGroup::make('Tax', ['taxStatus', 'gstPct', 'exemptReason', 'tax']),
                ])
                ->columns([
                    SerialColumn::make(),
                    SearchSelectColumn::make('resort_id')->label('Resort')
                        // A synthetic first dropdown entry that ENDS entry: it commits no value,
                        // it fires lgrid:complete. Only offered on a blank trailing row.
                        ->endOfListOption(allowOnEmpty: true)
                        ->optionsUsing(fn (string $term): array => Resort::query()
                            ->where('visibility', 'show')
                            ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                            ->orderBy('name')
                            ->limit(50)
                            ->get(['id', 'name', 'city', 'comparison_tariff'])
                            ->map(fn (Resort $resort): array => [
                                'value' => (string) $resort->id,
                                'label' => $resort->name,
                                // 'meta' paints right-aligned and muted in the option list —
                                // the stock-on-hand slot. Here: the resort's city and tariff.
                                'meta' => trim(($resort->city ?? '').'  ₹'.(int) $resort->comparison_tariff),
                            ])
                            ->all())
                        // Enrichment: picking a resort pre-fills the rate and city; clearing the
                        // pick clears them. Write-backs ride the op response, and the formula
                        // columns recompute AFTER this hook in the same round trip.
                        ->onSelect(function (RowContext $row, mixed $value): void {
                            if ($value === null) {
                                $row->set('rate', null)->set('city', null);

                                return;
                            }

                            $resort = Resort::query()->whereKey($value)
                                ->first(['name', 'city', 'comparison_tariff']);

                            $row->set('city', $resort?->city);
                            $row->set('rate', $resort?->comparison_tariff !== null
                                ? number_format((float) $resort->comparison_tariff, 2, '.', '')
                                : null);
                            // Keeps the picker's painted label right even after a reseed.
                            $row->setLabel('resort_id', (string) ($resort?->name ?? ''));
                        })
                        ->required()
                        ->minChars(0)->debounce(250)->limit(50)
                        ->minWidth(180)->grow(),
                    // Written by the hook above, never by the operator.
                    ReadonlyColumn::make('city')->label('City')->width(110),
                    DateColumn::make('fromDate')->label('From')->width(115)
                        ->displayFormat('d-M-Y')
                        ->required(),
                    DateColumn::make('toDate')->label('To')->width(115)
                        ->displayFormat('d-M-Y')
                        ->required(),
                    // Derived by afterCellChange() below, but still editable so the operator can
                    // override the computed count.
                    IntegerColumn::make('nights')->label('Nights')->width(80)->align('right')
                        ->rules(['integer', 'min:1', 'max:60'])
                        ->required(),
                    IntegerColumn::make('guests')->label('Guests')->width(80)->align('right')
                        ->rules(['integer', 'min:1', 'max:12'])
                        ->required(),
                    SelectColumn::make('plan')->label('Meal Plan')->options(self::PLANS)
                        ->width(150)->required(),
                    DecimalColumn::make('rate')->label('Rate / night')->scale(2)->width(120)
                        ->align('right')->format('number', ['scale' => 2])
                        ->rules(['numeric', 'min:0'])
                        // A per-row readonly closure is a SERVER verdict: a complimentary line's
                        // rate is locked at zero and the server refuses a write to it.
                        ->readonly(fn (array $row): bool => (bool) ($row['complimentary'] ?? false))
                        // …and required only when it is actually chargeable.
                        ->required(fn (array $row): bool => ! ($row['complimentary'] ?? false)),
                    // whenFilled() is a pure DECLARATION the client mirrors instantly: ticking
                    // the box zeroes the rate and blanks the GST% on the same row, with no
                    // round trip. afterCellChange() below is the authoritative twin.
                    CheckboxColumn::make('complimentary')->label('Comp?')->width(90)->align('center')
                        ->whenFilled(sets: ['rate' => '0.00', 'taxStatus' => 'exempt'], clears: ['gstPct']),
                    FormulaColumn::make('base')->label('Base')->width(110)->align('right')
                        ->formula('round(nights * rate, 2)'),
                    SelectColumn::make('taxStatus')->label('Tax')->options(self::TAX_STATUS)
                        ->width(110)->required(),
                    // lockedWhen(): the client can pre-evaluate a SIBLING-keyed lock, so the
                    // editor refuses these cells, serpentine navigation skips them, and they
                    // paint muted — instantly, with no server round trip.
                    DecimalColumn::make('gstPct')->label('GST %')->scale(2)->width(90)
                        ->align('right')
                        ->rules(['numeric', 'min:0', 'max:28'])
                        ->lockedWhen('taxStatus', 'exempt')
                        ->requiredWhen('taxStatus', 'taxable'),
                    TextColumn::make('exemptReason')->label('Exemption reason')->maxLength(60)
                        ->upper()                       // committed values are upper-cased
                        ->minWidth(160)
                        ->lockedWhen('taxStatus', 'taxable')
                        ->requiredWhen('taxStatus', 'exempt'),
                    // Formulas recompute in declaration order, so a later formula may read an
                    // earlier one: tax reads base, amount reads both.
                    FormulaColumn::make('tax')->label('GST')->width(100)->align('right')
                        ->formula('round(base * gstPct / 100, 2)'),
                    FormulaColumn::make('amount')->label('Amount')->width(120)->align('right')
                        ->formula('round(base + tax, 2)'),
                    YesNoColumn::make('confirmed')->label('Confirmed?')->width(105)->align('center'),
                    // opensPanel(): Enter here hands off to the HOST modal instead of advancing.
                    // The advance is stashed and resumes when the host fires lgrid:panel-done.
                    TextColumn::make('note')->label('Note (Enter opens the panel)')
                        ->maxLength(100)->minWidth(180)->grow()
                        ->opensPanel('line-notes'),
                    // Carried, unpainted — and ->writable() so ops may set it (a HiddenColumn is
                    // read-only by default).
                    HiddenColumn::make('line_id')->writable(),
                ])
                ->footer([
                    Aggregate::sum('nights')->format('number'),
                    Aggregate::sum('base')->format('number', ['scale' => 2]),
                    Aggregate::sum('tax')->format('number', ['scale' => 2]),
                    Aggregate::sum('amount')->format('number', ['scale' => 2]),
                ])
                // Runs after EVERY applied cell change (typing, paste, fill-down). Two jobs here:
                // derive nights from the date range, and be the AUTHORITATIVE twin of the
                // whenFilled() mirror declared on the Comp? column.
                ->afterCellChange(function (RowContext $row, string $column): void {
                    if ($column === 'complimentary') {
                        if ($row->get('complimentary')) {
                            $row->set('rate', '0.00')->set('taxStatus', 'exempt')->set('gstPct', null);
                        }

                        return;
                    }

                    if ($column === 'taxStatus') {
                        // Switching sides clears the cell the other side owns, so a stale value
                        // can never survive behind a lockedWhen() mask.
                        $row->get('taxStatus') === 'exempt'
                            ? $row->set('gstPct', null)
                            : $row->set('exemptReason', null);

                        return;
                    }

                    if (! in_array($column, ['fromDate', 'toDate'], true)) {
                        return;
                    }

                    $from = $row->get('fromDate');
                    $to = $row->get('toDate');

                    if (! $from || ! $to) {
                        return; // one side still blank — nothing to derive yet
                    }

                    $nights = (int) Carbon::parse($from)->startOfDay()
                        ->diffInDays(Carbon::parse($to)->startOfDay(), false);

                    // A reversed range derives nothing — clear nights so the required/min
                    // validation flags the row instead of silently keeping a stale count.
                    $row->set('nights', $nights >= 1 ? $nights : null);
                })
                // Fires after a row is deleted (Shift+Delete / F8 / the row menu): drop any
                // host-side extras the panel captured for a line that no longer exists.
                ->afterRowRemove(function (): void {
                    $live = array_column($this->lines, '_k');
                    $this->extras = array_intersect_key($this->extras, array_flip($live));
                })
                ->stickyHeader()
                ->freezeColumns(2)      // gutter + Resort stay put while you scroll right
                ->density(GridDensity::Normal)
                ->theme('emerald')
                ->statusBar()
                ->persistWidths()
                ->rowClass(fn (array $row): ?string => ($row['complimentary'] ?? false) ? 'row-comp' : null)
                ->cellClass(fn (mixed $value, array $row, string $column): ?string => $column === 'amount' && (float) $value > 100000
                    ? 'cell-big'
                    : null)
                ->maxHeight('55vh')
                ->emptyState('No booking lines yet — start typing a resort name.'),
        ];
    }

    /**
     * The ->opensPanel('line-notes') handler: the client dispatched lgrid:panel with the row
     * key; open the modal over this component's own state.
     */
    public function openPanel(string $rowKey): void
    {
        $this->panelRow = $rowKey;
        $this->panelRequests = $this->extras[$rowKey]['requests'] ?? '';
        $this->panelArrival = $this->extras[$rowKey]['arrival'] ?? '';
    }

    /**
     * Every panel exit path — OK, Cancel, Esc — must resume the grid, or the operator is left
     * with a cursor that never advanced.
     */
    public function closePanel(bool $keep = true): void
    {
        if ($keep && $this->panelRow !== null) {
            $this->extras[$this->panelRow] = [
                'requests' => $this->panelRequests,
                'arrival' => $this->panelArrival,
            ];
        }

        $this->panelRow = null;
        $this->panelRequests = '';
        $this->panelArrival = '';

        $this->gridPanelDone('lines');
    }

    /**
     * "Save": capture the cleaned rows (blank trailing rows stripped, client bookkeeping
     * removed), reset the grid to fresh seeded lines, and push the reset to the client
     * (reseedGrid — the mandatory step after any out-of-band rows mutation).
     */
    public function save(): void
    {
        $rows = $this->gridRows('lines');

        if ($rows === []) {
            return;
        }

        // Fold the panel-captured extras into the payload the host would persist.
        $this->saved = array_map(fn (array $row): array => $row + [
            '_extras' => $this->extras[$row['_k'] ?? ''] ?? null,
        ], $rows);

        $this->lines = $this->gridMountRows('lines');
        $this->extras = [];
        $this->reseedGrid('lines');
    }

    public function render(): View
    {
        // Server-side totals over the bound rows — kept live by ->refreshesHost().
        $rows = array_filter($this->lines, fn (array $row): bool => ! empty($row['resort_id']));

        return view('livewire.booking-entry', [
            'lineCount' => count($rows),
            'runningTotal' => array_sum(array_map(fn (array $row): float => (float) ($row['amount'] ?? 0), $rows)),
        ]);
    }
}
