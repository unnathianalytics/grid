<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Grid\Columns\RatingColumn;
use App\Models\Resort;
use Illuminate\View\View;
use LaraGrid\Actions\Action;
use LaraGrid\Aggregate;
use LaraGrid\ColumnGroup;
use LaraGrid\Columns\ComputedColumn;
use LaraGrid\Columns\DecimalColumn;
use LaraGrid\Columns\IntegerColumn;
use LaraGrid\Columns\SerialColumn;
use LaraGrid\Columns\TextColumn;
use LaraGrid\Grid;
use LaraGrid\GridDensity;
use LaraGrid\Support\CellHtml;

/**
 * The DISPLAY-MODE showcase — LaraGrid on a plain Blade page with no Livewire component
 * anywhere in sight.
 *
 * A display grid declares neither ->query() nor ->editable(): rows are handed to the tag
 * (<x-laragrid :grid :rows>) and painted as-is, with the same keyboard model, selection
 * engine, column chooser, resizing and footer totals as the other two modes. It is the mode
 * for computed report grids — trial balances, ageings, roll-ups — that can never be
 * query()-backed because their rows do not exist as table rows.
 *
 * Unique to this mode:
 *   · ->sortable() sorts CLIENT-SIDE — stable, type-aware, empties last, click cycling
 *     asc → desc → original order, with ->defaultSort() applied at load
 *   · F9 / Shift+F9 temporarily hide and restore rows, and the footer aggregates recompute
 *     over what is left — the what-if view an accountant actually wants
 *   · only url() row actions are allowed: an in-memory grid has no authoritative server-side
 *     row source, so call() and bulk actions are refused at build time
 *
 * This page also carries the EXTENSION demo: a custom column type (RatingColumn), a custom
 * painter, a custom display format ('inr') and a custom parse kind ('stars') — PHP halves in
 * App\Grid\* and App\Providers\AppServiceProvider, JavaScript twins in the view.
 */
class ReportsController extends Controller
{
    /** A small in-memory table — rows that exist nowhere in the database. */
    private const AGEING = [
        ['bucket' => 'Not due', 'invoices' => 128, 'amount' => 4128500, 'weight' => 0],
        ['bucket' => '1 – 30 days', 'invoices' => 74, 'amount' => 2260750, 'weight' => 15],
        ['bucket' => '31 – 60 days', 'invoices' => 41, 'amount' => 1189300, 'weight' => 35],
        ['bucket' => '61 – 90 days', 'invoices' => 22, 'amount' => 742900, 'weight' => 60],
        ['bucket' => '91 – 180 days', 'invoices' => 13, 'amount' => 415600, 'weight' => 80],
        ['bucket' => 'Over 180 days', 'invoices' => 7, 'amount' => 233450, 'weight' => 100],
    ];

    public function index(): View
    {
        $cityRows = $this->cityRows();

        return view('reports.index', [
            'cityGrid' => $this->cityGrid(),
            'cityRows' => $cityRows,
            'ageingGrid' => $this->ageingGrid(),
            'ageingRows' => $this->ageingRows(),
        ]);
    }

    /**
     * The roll-up: one row per city, computed by the database but shaped here — exactly the
     * kind of result set display mode exists for.
     *
     * @return list<array<string, mixed>>
     */
    private function cityRows(): array
    {
        $rows = Resort::query()
            ->selectRaw('city')
            ->selectRaw('COUNT(*) as properties')
            ->selectRaw('COALESCE(SUM(rooms), 0) as rooms')
            ->selectRaw('COALESCE(AVG(comparison_tariff), 0) as avg_tariff')
            ->selectRaw('COALESCE(SUM(comparison_tariff), 0) as tariff_total')
            ->selectRaw('COALESCE(SUM(hits), 0) as hits')
            ->whereNotNull('city')
            ->groupBy('city')
            ->orderBy('city')
            ->get()
            ->map(fn ($row): array => [
                'city' => (string) $row->city,
                'properties' => (int) $row->properties,
                'rooms' => (int) $row->rooms,
                'avg_tariff' => number_format((float) $row->avg_tariff, 2, '.', ''),
                'tariff_total' => (int) $row->tariff_total,
                'hits' => (int) $row->hits,
                // Feeds the custom RatingColumn: a 0–5 score off the average tariff.
                'rating' => max(0, min(5, (int) round(((float) $row->avg_tariff) / 2500))),
            ])
            ->all();

        $totalHits = max(1, array_sum(array_column($rows, 'hits')));

        return array_map(fn (array $row): array => $row + [
            'share' => round($row['hits'] * 100 / $totalHits, 2),
        ], $rows);
    }

    /**
     * The main report grid. No ->query(), no ->editable() — display mode.
     */
    private function cityGrid(): Grid
    {
        return Grid::make('city-report')
            // No authorize() is required in display mode: there is no server data surface to
            // gate — the host already decided what rows to hand over. (For the same reason
            // ->exportable() and ->savedViews() are refused here: both need a query() grid.)
            ->defaultSort('city')
            ->columnGroups([
                ColumnGroup::make('Inventory', ['properties', 'rooms']),
                ColumnGroup::make('Tariff', ['avg_tariff', 'tariff_total', 'rating']),
                ColumnGroup::make('Demand', ['hits', 'share']),
            ])
            ->columns([
                SerialColumn::make(),
                // Client-side sorting: no argument is allowed (a DB sort target would be
                // meaningless here and fails loudly at build time).
                TextColumn::make('city')->label('City')->sortable()->minWidth(150)->grow(),
                IntegerColumn::make('properties')->label('Properties')->sortable()
                    ->width(110)->align('right')->format('number'),
                IntegerColumn::make('rooms')->label('Rooms')->sortable()
                    ->width(100)->align('right')->format('number'),
                // The app-registered 'inr' format — Indian digit grouping, PHP + JS twins.
                DecimalColumn::make('avg_tariff')->label('Avg tariff')->scale(2)->sortable()
                    ->width(130)->align('right')->format('inr', ['scale' => 2]),
                IntegerColumn::make('tariff_total')->label('Tariff total')->sortable()
                    ->width(140)->align('right')->format('inr'),
                // The app-defined column type, drawn by the app-registered 'rating' painter.
                RatingColumn::make('rating')->label('Tier')->sortable(),
                IntegerColumn::make('hits')->label('Page views')->sortable()
                    ->width(120)->align('right')->format('number'),
                ComputedColumn::make('share')->label('Share')->html()->width(150)
                    ->state(fn (array $row): string => sprintf(
                        '<span class="bar"><span class="bar-fill" style="width:%s%%"></span></span><span class="bar-num">%s%%</span>',
                        min(100, (float) ($row['share'] ?? 0) * 4),
                        number_format((float) ($row['share'] ?? 0), 2),
                    )),
            ])
            ->footer([
                Aggregate::sum('properties')->format('number'),
                Aggregate::sum('rooms')->format('number'),
                Aggregate::sum('tariff_total')->format('inr'),
                Aggregate::sum('hits')->format('number'),
            ])
            // Display grids may declare url() row actions only: with no server-side row
            // source there is nothing for a call() action to re-resolve and re-authorize.
            ->actions([
                Action::make('browse')->label('Browse in the register')->icon('→')
                    ->url(fn (): string => route('resorts.index')),
            ])
            ->stickyHeader()
            ->freezeColumns(2)
            ->striped()
            ->density(GridDensity::Normal)
            ->theme('zinc')
            ->statusBar()
            ->persistWidths()
            ->maxHeight('60vh')
            ->emptyState('No cities to report on.');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function ageingRows(): array
    {
        return array_map(fn (array $row): array => $row + [
            'provision' => (int) round($row['amount'] * $row['weight'] / 100),
        ], self::AGEING);
    }

    /**
     * The what-if grid: hand-built rows, no toolbar, a fixed height — and F9 as the point.
     */
    private function ageingGrid(): Grid
    {
        return Grid::make('ageing')
            ->toolbar(false)            // no search, no filters, no chooser — bare chrome
            ->height('260px')           // a fixed box rather than a content-sized one
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
            ->emptyState('Nothing outstanding.');
    }
}
