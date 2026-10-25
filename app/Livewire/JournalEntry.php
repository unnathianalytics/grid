<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use LaraGrid\Aggregate;
use LaraGrid\Columns\DecimalColumn;
use LaraGrid\Columns\FocusMode;
use LaraGrid\Columns\HiddenColumn;
use LaraGrid\Columns\SearchSelectColumn;
use LaraGrid\Columns\SelectColumn;
use LaraGrid\Columns\SerialColumn;
use LaraGrid\Columns\TextColumn;
use LaraGrid\Editing\RowContext;
use LaraGrid\Grid;
use LaraGrid\GridDensity;
use LaraGrid\Livewire\WithLaraGrid;
use LaraGrid\SyncPolicy;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The DOUBLE-ENTRY showcase — the accounting shape LaraGrid was extracted from.
 *
 * Where the booking grid ends entry with a picker exit, this one ends it by BALANCING:
 * `->completeWhenBalanced('dr', 'cr')` keeps auto-appending while Σdr ≠ Σcr and, the moment
 * the two sides agree (both above zero), turns Enter-past-the-last-cell into the completion
 * signal instead of another row. With autofill on, landing on an empty amount cell of the
 * deficit side pre-fills the balancing figure through the normal commit pipeline — the
 * operator accepts it with Enter or overtypes it.
 *
 * Also covered here, and nowhere else in the demo:
 *   · a FocusMode::Manual column — the ERP "semi-variable" field: Enter and Tab skip it, a
 *     mouse click still edits it (the Group selector that narrows the ledger picker)
 *   · row-dependent picker options — optionsUsing(fn ($term, $row)) reads the row's Group,
 *     and optionsDependOn(['group']) keys the client's option cache by it, so one row's
 *     list is never reused for a row on a different group
 *   · SyncPolicy::PerRow — ops batch until the cursor leaves the row, instead of per cell
 *   · a mutually exclusive Dr / Cr column pair under a D/C selector, declared with
 *     whenFilled() mirrors + lockedWhen() masks and reconciled by an authoritative
 *     afterCellChange() hook (typed side wins)
 *   · optionsUsing() over a plain PHP array — an async picker needs no database
 *   · newRowUsing() as the grid-level row template (the booking grid uses per-column
 *     ->default() instead — both seed the same blank-row template)
 *   · a Post button that only enables on the very commit that completes the voucher, which
 *     onCompleteFocus() still focuses thanks to its retrying selector lookup, and which posts
 *     through grid.commitAndSave('post') so it can never race an open editor
 *
 * Nothing is written to the database — Post captures the cleaned rows and shows them.
 */
#[Layout('components.layouts.app', ['wide' => true])]
class JournalEntry extends Component
{
    use WithLaraGrid;

    /** A miniature chart of accounts — the picker's option source. The leading digit is the group. */
    private const LEDGERS = [
        '1001' => 'Cash in Hand',
        '1002' => 'Bank — Current A/c',
        '1101' => 'Trade Receivables',
        '1201' => 'Prepaid Expenses',
        '1301' => 'Furniture & Fixtures',
        '2001' => 'Trade Payables',
        '2101' => 'GST Payable',
        '2201' => 'TDS Payable',
        '3001' => 'Capital A/c',
        '4001' => 'Room Revenue',
        '4002' => 'Food & Beverage Revenue',
        '5001' => 'Salaries & Wages',
        '5002' => 'Housekeeping Supplies',
        '5003' => 'Electricity & Water',
        '5004' => 'Repairs & Maintenance',
        '5005' => 'Commission — Travel Agents',
    ];

    /** Ledger groups, keyed by the account code's leading digit. */
    private const GROUPS = [
        '1' => 'Assets',
        '2' => 'Liabilities',
        '3' => 'Capital',
        '4' => 'Income',
        '5' => 'Expenses',
    ];

    private const SIDES = ['D' => 'Dr', 'C' => 'Cr'];

    /** @var list<array<string, mixed>> The grid-bound rows (each carries a stable _k). */
    public array $entries = [];

    /** @var array<string, mixed> The last posted voucher (header + cleaned lines), for display. */
    public array $posted = [];

    public string $voucherDate = '';

    public string $narration = '';

    public function mount(): void
    {
        $this->entries = $this->gridMountRows('voucher');
        $this->voucherDate = now()->toDateString();
    }

    /**
     * @return array<string, Grid>
     */
    protected function grids(): array
    {
        return [
            'voucher' => Grid::make('voucher')
                ->editable()
                ->rowsFrom('entries')
                ->authorize(fn (): bool => true)
                ->defaultRows(4)
                // The grid-level new-row template. Column ->default()s are applied first, then
                // this overlay — and BOTH are part of the blank-row template, so a row that
                // still reads 'D' here holds no operator data.
                ->newRowUsing(fn (): array => ['dc' => 'D'])
                ->minRows(2)
                ->autoAppend()
                ->padRows(2)
                // PerRow: a row's ops queue until the cursor leaves it, then flush as one batch.
                // Fewer round trips on a wide row; the trade-off is that validation feedback for
                // a cell arrives when the row is left rather than when the cell is committed.
                ->sync(SyncPolicy::PerRow)
                ->keymap('entry')
                ->focusOnMount()
                ->focusOutTo('#narration')
                // The Post button below is disabled until the voucher balances — i.e. until the
                // very commit that fires this. The retrying lookup is what makes that work.
                ->onCompleteFocus('[data-post]')
                // While Σdr ≠ Σcr the grid keeps growing; once they agree (both > 0) Enter past
                // the last cell fires lgrid:complete instead. autofill: true (the default)
                // pre-fills the balancing amount on the deficit side.
                ->completeWhenBalanced('dr', 'cr', autofill: true)
                ->refreshesHost(['dr', 'cr'])
                ->columns([
                    SerialColumn::make(),
                    // FocusMode::Manual — the "semi-variable" field of voucher entry: serpentine
                    // Enter and Tab never stop here, a mouse click still opens it. An operator
                    // who knows the ledger name flies past; one who doesn't clicks it to narrow
                    // the picker on the next cell. (FocusMode::Never would also lock it; the
                    // default, FocusMode::Always, is the ordinary stop-here field.)
                    SelectColumn::make('group')->label('Group')->options(self::GROUPS)
                        ->focusMode(FocusMode::Manual)
                        ->width(110),
                    // An async picker needs no database: optionsUsing() may return anything. It
                    // also receives the ROW, so the list can depend on a sibling cell — here the
                    // optional Group narrows the chart of accounts.
                    SearchSelectColumn::make('ledger')->label('Particulars')
                        ->optionsUsing(fn (string $term, array $row): array => collect(self::LEDGERS)
                            ->filter(fn (string $name, string $code): bool => ($row['group'] ?? null) === null
                                || str_starts_with($code, (string) $row['group']))
                            ->filter(fn (string $name, string $code): bool => $term === ''
                                || str_contains(strtolower($name), strtolower($term))
                                || str_starts_with($code, $term))
                            ->map(fn (string $name, string $code): array => [
                                'value' => $code,
                                'label' => $name,
                                'meta' => $code,
                            ])
                            ->values()->all())
                        // Declares the dependency: the client caches options per typed term, and
                        // this keys that cache by the row's group too — so the list fetched for
                        // an Expenses row is never reused for an Income row. The server still
                        // re-runs the closure with the row, so the filtering stays authoritative.
                        ->optionsDependOn(['group'])
                        ->onSelect(function (RowContext $row, mixed $value): void {
                            // Enrichment: carry the account code alongside the picked id, so the
                            // posted payload needs no second lookup — and fill in the group the
                            // pick belongs to, so the Manual column reads right even if skipped.
                            $code = $value === null ? null : (string) $value;
                            $row->set('code', $code);
                            $row->set('group', $code === null ? null : substr($code, 0, 1));
                        })
                        ->required()
                        ->minChars(0)->debounce(200)->limit(20)
                        ->minWidth(240)->grow(),
                    SelectColumn::make('dc')->label('Dr/Cr')->options(self::SIDES)
                        ->width(90)->align('center')->required(),
                    // The mutually exclusive pair. whenFilled() is the CLIENT mirror (instant,
                    // no round trip); lockedWhen() masks the side the selector rules out; the
                    // afterCellChange() hook below is the authoritative implementation.
                    DecimalColumn::make('dr')->label('Debit')->scale(2)->width(140)
                        ->align('right')->format('number', ['scale' => 2])
                        ->rules(['numeric', 'min:0'])
                        ->lockedWhen('dc', 'C')
                        ->whenFilled(sets: ['dc' => 'D'], clears: ['cr']),
                    DecimalColumn::make('cr')->label('Credit')->scale(2)->width(140)
                        ->align('right')->format('number', ['scale' => 2])
                        ->rules(['numeric', 'min:0'])
                        ->lockedWhen('dc', 'D')
                        ->whenFilled(sets: ['dc' => 'C'], clears: ['dr']),
                    TextColumn::make('narration')->label('Line narration')->maxLength(120)
                        ->minWidth(200)->grow(),
                    HiddenColumn::make('code')->writable(),
                ])
                ->footer([
                    Aggregate::sum('dr')->format('number', ['scale' => 2]),
                    Aggregate::sum('cr')->format('number', ['scale' => 2]),
                ])
                // Typed side wins: whichever amount the operator actually typed sets the
                // selector and blanks the opposite cell. Flipping the selector by hand clears
                // the amount the new side no longer owns. Changing the group drops a ledger
                // that no longer belongs to it.
                ->afterCellChange(function (RowContext $row, string $column): void {
                    if ($column === 'group') {
                        $ledger = (string) ($row->get('ledger') ?? '');
                        $group = $row->get('group');

                        if ($ledger !== '' && $group !== null && ! str_starts_with($ledger, (string) $group)) {
                            $row->set('ledger', null)->set('code', null)->setLabel('ledger', '');
                        }

                        return;
                    }

                    if ($column === 'dr' && $row->get('dr') !== null && $row->get('dr') !== '') {
                        $row->set('dc', 'D')->set('cr', null);

                        return;
                    }

                    if ($column === 'cr' && $row->get('cr') !== null && $row->get('cr') !== '') {
                        $row->set('dc', 'C')->set('dr', null);

                        return;
                    }

                    if ($column === 'dc') {
                        $row->get('dc') === 'D'
                            ? $row->set('cr', null)
                            : $row->set('dr', null);
                    }
                })
                ->afterRowRemove(fn () => null)   // hook point: recompute host chrome after a delete
                ->stickyHeader()
                ->freezeColumns(2)
                ->density(GridDensity::Compact)
                ->theme('violet')
                ->statusBar()
                ->persistWidths()
                ->rowClass(fn (array $row): ?string => match ($row['dc'] ?? null) {
                    'D' => 'row-dr',
                    'C' => 'row-cr',
                    default => null,
                })
                ->maxHeight('50vh')
                ->emptyState('No voucher lines yet.'),
        ];
    }

    /** Σdr − Σcr over the bound rows, kept live by ->refreshesHost(['dr', 'cr']). */
    public function getDifferenceProperty(): float
    {
        $sum = fn (string $column): float => array_sum(
            array_map(fn (array $row): float => (float) ($row[$column] ?? 0), $this->entries)
        );

        return round($sum('dr') - $sum('cr'), 2);
    }

    public function getIsBalancedProperty(): bool
    {
        $debits = array_sum(array_map(fn (array $row): float => (float) ($row['dr'] ?? 0), $this->entries));

        return $debits > 0 && abs($this->difference) < 0.005;
    }

    /**
     * Invoked by grid.commitAndSave('post') — i.e. only after the open editor has committed and
     * every queued op is acknowledged — never by a bare wire:click racing the grid.
     */
    public function post(): void
    {
        if (! $this->isBalanced) {
            return;
        }

        $this->posted = [
            'date' => $this->voucherDate,
            'narration' => $this->narration,
            'lines' => $this->gridRows('voucher'),
        ];

        $this->entries = $this->gridMountRows('voucher');
        $this->narration = '';
        $this->reseedGrid('voucher');
    }

    public function render(): View
    {
        return view('livewire.journal-entry');
    }
}
