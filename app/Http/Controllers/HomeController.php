<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Resort;
use Illuminate\View\View;
use LaraGrid\Aggregate;
use LaraGrid\Columns\ComputedColumn;
use LaraGrid\Columns\DateColumn;
use LaraGrid\Columns\IntegerColumn;
use LaraGrid\Columns\SerialColumn;
use LaraGrid\Columns\TextColumn;
use LaraGrid\Grid;
use LaraGrid\GridDensity;
use LaraGrid\Support\CellHtml;

/**
 * The overview page — what LaraGrid is, what it costs to install, and a working grid above
 * the fold so the claim is checkable in one scroll.
 *
 * The teaser is a DISPLAY-mode grid (rows handed to the tag, no Livewire component), which
 * keeps the landing page a plain cached Blade render while still being the real component.
 */
class HomeController extends Controller
{
    public function index(): View
    {
        return view('home', [
            'grid' => $this->teaser(),
            'rows' => $this->rows(),
            'resortCount' => Resort::query()->count(),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(): array
    {
        return Resort::query()
            ->whereNotNull('city')
            ->orderByDesc('hits')
            ->limit(8)
            ->get(['id', 'name', 'type', 'city', 'star_rating', 'comparison_tariff', 'rooms', 'hits', 'created_at', 'visibility'])
            ->map(fn (Resort $resort): array => [
                'id' => (int) $resort->id,
                'name' => (string) $resort->name,
                'type' => (string) $resort->type,
                'city' => (string) $resort->city,
                'tariff' => (int) $resort->comparison_tariff,
                'rooms' => (int) $resort->rooms,
                'hits' => (int) $resort->hits,
                'created_at' => optional($resort->created_at)->toDateString(),
                'visibility' => (string) ($resort->visibility ?? 'show'),
            ])
            ->all();
    }

    private function teaser(): Grid
    {
        return Grid::make('teaser')
            ->toolbar(false)
            ->defaultSort('hits', 'desc')
            ->columns([
                SerialColumn::make(),
                TextColumn::make('name')->label('Resort')->sortable()->minWidth(200)->grow(),
                TextColumn::make('type')->label('Type')->sortable()->width(120),
                TextColumn::make('city')->label('City')->sortable()->width(120),
                IntegerColumn::make('tariff')->label('Tariff')->sortable()->width(110)
                    ->align('right')->format('inr'),
                IntegerColumn::make('rooms')->label('Rooms')->sortable()->width(90)->align('right'),
                IntegerColumn::make('hits')->label('Views')->sortable()->width(100)
                    ->align('right')->format('number'),
                ComputedColumn::make('status')->label('Status')->html()->width(90)->align('center')
                    ->state(fn (array $row): string => ($row['visibility'] ?? 'show') === 'show'
                        ? CellHtml::badge('green', 'Live')
                        : CellHtml::badge('zinc', 'Hidden')),
                DateColumn::make('created_at')->label('Added')->sortable()->width(110),
            ])
            ->footer([
                Aggregate::sum('rooms')->format('number'),
                Aggregate::sum('hits')->format('number'),
            ])
            ->stickyHeader()
            ->striped()
            ->statusBar()
            ->density(GridDensity::Compact)
            ->theme('blue')
            ->maxHeight('none');
    }
}
