<?php

namespace App\Livewire;

use App\Models\Resort;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use LaraGrid\Aggregate;
use LaraGrid\Columns\DateColumn;
use LaraGrid\Columns\DecimalColumn;
use LaraGrid\Columns\FormulaColumn;
use LaraGrid\Columns\IntegerColumn;
use LaraGrid\Columns\SearchSelectColumn;
use LaraGrid\Columns\SerialColumn;
use LaraGrid\Columns\TextColumn;
use LaraGrid\Columns\YesNoColumn;
use LaraGrid\Editing\RowContext;
use LaraGrid\Grid;
use LaraGrid\Livewire\WithLaraGrid;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Editable LaraGrid demo — a booking-lines entry screen over the real resorts table.
 *
 * Exercises the full editing machinery: async resort picker (gridOptions RPC) with
 * onSelect enrichment (rate pre-filled from the resort's comparison_tariff), typed
 * cells with client+server validation, a dual-runtime formula column (nights × rate),
 * auto-append, live footer totals, keyboard row ops (Insert, Shift+Delete/F7, Ctrl+D,
 * Delete-clears), and the save path: gridRows() cleaning + reseedGrid().
 *
 * Nothing writes to the database — Save captures the cleaned rows and shows them.
 */
#[Title('Booking Entry')]
#[Layout('components.layouts.app')]
class BookingEntry extends Component
{
    use WithLaraGrid;

    /** @var list<array<string, mixed>> The grid-bound rows (each carries a stable _k). */
    public array $lines = [];

    /** @var list<array<string, mixed>> The last saved (cleaned) payload, for display. */
    public array $saved = [];

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
                ->authorize(fn(): bool => true)
                ->defaultRows(3)
                //->newRowUsing(fn(): array => ['nights' => 1])
                ->minRows(1)
                ->autoAppend()
                ->padRows(4)
                ->focusOnMount()
                ->focusOutTo('[data-save]')
                ->columns([
                    SerialColumn::make(),
                    SearchSelectColumn::make('resort_id')->label('Resort')
                        ->endOfListOption(allowOnEmpty: true)
                        ->optionsUsing(fn(string $term): array => Resort::query()
                            ->where('visibility', 'show')
                            ->when($term !== '', fn($q) => $q->where('name', 'like', "%{$term}%"))
                            ->orderBy('name')
                            ->limit(50)
                            ->get(['id', 'name'])
                            ->map(fn(Resort $resort): array => [
                                'value' => (string) $resort->id,
                                'label' => $resort->name,
                            ])
                            ->all())
                        // Enrichment: picking a resort pre-fills the rate from its tariff;
                        // clearing the pick clears the rate. Write-backs ride the op response.
                        ->onSelect(function (RowContext $row, mixed $value): void {
                            if ($value === null) {
                                $row->set('rate', null);

                                return;
                            }
                            $tariff = Resort::whereKey($value)->value('comparison_tariff');
                            $row->set('rate', $tariff !== null
                                ? number_format((float) $tariff, 2, '.', '')
                                : null);
                        })
                        ->required()
                        ->minChars(0)->debounce(250)->limit(50)
                        ->grow(),
                    DateColumn::make('fromDate')->label('From'),
                    DateColumn::make('toDate')->label('To'),
                    IntegerColumn::make('nights')->label('Nights')
                        ->rules(['integer', 'min:1', 'max:60'])
                        ->required()
                        ->width(90),
                    YesNoColumn::make('taxable')->label('Taxable?'),
                    DecimalColumn::make('rate')->label('Rate / night')->scale(2)
                        ->rules(['numeric', 'min:0'])
                        ->width(120),
                    FormulaColumn::make('amount')->label('Amount')
                        ->formula('round(nights * rate, 2)')
                        ->width(130),
                    TextColumn::make('note')->label('Note')->maxLength(100)->grow(),
                ])
                ->footer([
                    Aggregate::sum('amount')->format('number', ['scale' => 2]),
                ])
                // Derived nights: whenever From or To changes (typing, paste, fill-down),
                // recompute nights = date difference. The hook runs server-side after the
                // cell is applied; its write-back patches the client row, and the amount
                // FORMULA recomputes AFTER the hook in the same op — so one date commit
                // updates Nights and Amount together in a single round trip. Nights stays
                // editable, so the operator can still override the computed value.
                ->afterCellChange(function (RowContext $row, string $column): void {
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
                ->stickyHeader()
                ->maxHeight('55vh')
                ->emptyState('No booking lines yet.'),
        ];
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

        $this->saved = $rows;
        $this->lines = $this->gridMountRows('lines');
        $this->reseedGrid('lines');
    }

    public function render(): View
    {
        return view('livewire.booking-entry');
    }
}
