<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use LaraGrid\Aggregate;
use LaraGrid\Columns\DecimalColumn;
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
 *   · SyncPolicy::PerRow — ops batch until the cursor leaves the row, instead of per cell
 *   · a mutually exclusive Dr / Cr column pair under a D/C selector, declared with
 *     whenFilled() mirrors + lockedWhen() masks and reconciled by an authoritative
 *     afterCellChange() hook (typed side wins)
 *   · optionsUsing() over a plain PHP array — an async picker needs no database
 *   · a Post button that only enables on the very commit that completes the voucher, which
 *     onCompleteFocus() still focuses thanks to its retrying selector lookup
 *
 * Nothing is written to the database — Post captures the cleaned rows and shows them.
 */
#[Layout('components.layouts.app', ['wide' => true])]
class JournalEntry extends Component
{
    use WithLaraGrid;

    /** A miniature chart of accounts — the picker's option source. */
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
                    // An async picker needs no database: optionsUsing() may return anything.
                    SearchSelectColumn::make('ledger')->label('Particulars')
                        ->optionsUsing(fn (string $term): array => collect(self::LEDGERS)
                            ->filter(fn (string $name, string $code): bool => $term === ''
                                || str_contains(strtolower($name), strtolower($term))
                                || str_starts_with($code, $term))
                            ->map(fn (string $name, string $code): array => [
                                'value' => $code,
                                'label' => $name,
                                'meta' => $code,
                            ])
                            ->values()->all())
                        ->onSelect(function (RowContext $row, mixed $value): void {
                            // Enrichment: carry the account code alongside the picked id, so the
                            // posted payload needs no second lookup.
                            $row->set('code', $value === null ? null : (string) $value);
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
                // the amount the new side no longer owns.
                ->afterCellChange(function (RowContext $row, string $column): void {
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
