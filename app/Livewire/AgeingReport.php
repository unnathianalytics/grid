<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use LaraGrid\Aggregate;
use LaraGrid\Columns\ComputedColumn;
use LaraGrid\Columns\IntegerColumn;
use LaraGrid\Columns\SerialColumn;
use LaraGrid\Columns\TextColumn;
use LaraGrid\Grid;
use LaraGrid\GridDensity;
use LaraGrid\Livewire\WithLaraGrid;
use LaraGrid\Support\CellHtml;
use Livewire\Component;

/**
 * The TRUSTED-EXPORT showcase — a display-mode grid that still offers CSV / XLSX / PDF
 * downloads, because it declares a server-side row resolver.
 *
 * Display mode paints whatever rows the host hands to the tag, and LaraGrid will never
 * export those: Blade rows, a Livewire public property, the DOM, rows included in the
 * export request — none of them is a trusted source. ->exportRows() is the separate,
 * trusted half of the contract: a closure that rebuilds the rows ON THE SERVER, after
 * ->authorize(), from data the host owns, with only the normalized current view
 * {sort, dir, search, filters} as input. Rendering stays in-memory — isServerSide() is still
 * false and there is no gridFetch; only the ⤓ Export control gains a server source.
 *
 * It is a Livewire component (unlike the city roll-up above it on the page) because exports
 * are an RPC on the host — and a small NESTED component is all that takes; the page around
 * it stays a plain Blade view.
 *
 * Also here: ->fitColumns() — every visible column is kept inside the box (declared widths
 * become proportional weights, and hiding or revealing a column rebalances the rest), so a
 * fixed-height report never grows a horizontal scrollbar.
 */
class AgeingReport extends Component
{
    use WithLaraGrid;

    /** A small in-memory table — rows that exist nowhere in the database. */
    private const AGEING = [
        ['bucket' => 'Not due', 'invoices' => 128, 'amount' => 4128500, 'weight' => 0],
        ['bucket' => '1 – 30 days', 'invoices' => 74, 'amount' => 2260750, 'weight' => 15],
        ['bucket' => '31 – 60 days', 'invoices' => 41, 'amount' => 1189300, 'weight' => 35],
        ['bucket' => '61 – 90 days', 'invoices' => 22, 'amount' => 742900, 'weight' => 60],
        ['bucket' => '91 – 180 days', 'invoices' => 13, 'amount' => 415600, 'weight' => 80],
        ['bucket' => 'Over 180 days', 'invoices' => 7, 'amount' => 233450, 'weight' => 100],
    ];

    /**
     * @return array<string, Grid>
     */
    protected function grids(): array
    {
        return [
            'ageing' => Grid::make('ageing')
                // No ->query(), no ->editable(): display mode. Rows are handed to the tag.
                //
                // Exports are a server surface, so the gate is mandatory and fail-closed — the
                // build refuses exportRows() without authorize(). Demo app has no auth.
                ->authorize(fn (): bool => true)
                // The trusted row source. It receives only {sort, dir, search, filters} — never
                // the painted rows — and rebuilds the report from what the server knows.
                ->exportRows(fn (array $state): iterable => $this->exportRows($state))
                ->exportable(['csv', 'xlsx', 'pdf'], fileName: 'receivables-ageing')
                ->height('280px')           // a fixed box rather than a content-sized one
                ->fitColumns()              // and no horizontal scrollbar inside it, ever
                ->columns([
                    SerialColumn::make(),
                    TextColumn::make('bucket')->label('Ageing bucket')->sortable()->minWidth(150)->grow(),
                    IntegerColumn::make('invoices')->label('Invoices')->sortable()
                        ->width(100)->align('right')->format('number'),
                    IntegerColumn::make('amount')->label('Outstanding')->sortable()
                        ->width(150)->align('right')->format('inr'),
                    IntegerColumn::make('weight')->label('Provision %')->sortable()
                        ->width(110)->align('right'),
                    IntegerColumn::make('provision')->label('Provision')->sortable()
                        ->width(140)->align('right')->format('inr'),
                    ComputedColumn::make('health')->label('Health')->html()->width(100)->align('center')
                        // Painted chrome exports as plain text ("At risk"), never as markup.
                        ->state(fn (array $row): string => match (true) {
                            ($row['weight'] ?? 0) >= 60 => CellHtml::badge('red', 'At risk'),
                            ($row['weight'] ?? 0) >= 15 => CellHtml::badge('amber', 'Watch'),
                            default => CellHtml::badge('green', 'Current'),
                        }),
                ])
                ->footer([
                    Aggregate::sum('invoices')->format('number'),
                    Aggregate::sum('amount')->format('inr'),
                    Aggregate::sum('provision')->format('inr'),
                ])
                ->density(GridDensity::Comfortable)
                ->theme('amber')
                ->striped()
                ->statusBar()
                ->emptyState('Nothing outstanding.'),
        ];
    }

    /**
     * The rows the PAGE paints — the `:rows` half of the contract. Computed, not stored.
     *
     * @return list<array<string, mixed>>
     */
    private function rows(): array
    {
        return array_map(fn (array $row): array => $row + [
            'provision' => (int) round($row['amount'] * $row['weight'] / 100),
        ], self::AGEING);
    }

    /**
     * The rows a DOWNLOAD contains — rebuilt here, on the server, from the same source the
     * page was built from. $state is exactly {sort, dir, search, filters}: unknown client keys
     * and invalid sort targets have already been removed. This report has no search or
     * filters, so only the current sort is honoured — the file then matches the screen.
     *
     * A generator keeps the export pipeline bounded-memory on a report of any size.
     *
     * @param  array{sort: string|null, dir: string, search: string, filters: array<string, string>}  $state
     * @return iterable<int, array<string, mixed>>
     */
    private function exportRows(array $state): iterable
    {
        $rows = $this->rows();

        if ($state['sort'] !== null) {
            $column = $state['sort'];
            $direction = $state['dir'] === 'desc' ? -1 : 1;

            usort($rows, fn (array $a, array $b): int => $direction * (($a[$column] ?? null) <=> ($b[$column] ?? null)));
        }

        yield from $rows;
    }

    public function render(): View
    {
        return view('livewire.ageing-report', ['rows' => $this->rows()]);
    }
}
