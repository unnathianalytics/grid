<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;
use LaraGrid\Aggregate;
use LaraGrid\Columns\ComputedColumn;
use LaraGrid\Columns\DecimalColumn;
use LaraGrid\Columns\IntegerColumn;
use LaraGrid\Columns\SerialColumn;
use LaraGrid\Columns\TextColumn;
use LaraGrid\Grid;
use LaraGrid\GridDensity;
use LaraGrid\Support\CellHtml;

/**
 * The THEMING showcase — one identical grid rendered under every shipped color scheme, a
 * custom two-token scheme, and all three row densities.
 *
 * How theming works: every visual is a `--lgrid-*` CSS custom property with a self-contained
 * default, so the grid looks right on a page with no CSS framework at all (this demo has no
 * Tailwind build). Each shipped scheme is really just an ACCENT PAIR — light and dark — and
 * every other surface (header, footer, stripes, borders, selection tint, focus ring) derives
 * from it through a shared color-mix formula. That is why adding your own scheme is two
 * custom properties on a class you hand to ->themeClass(), and why dark mode is nothing but
 * token flipping under a `.dark` ancestor.
 */
class ThemesController extends Controller
{
    /** The presets that ship with the package; ->theme() validates against exactly this list. */
    private const THEMES = [
        'zinc' => 'Neutral zinc — the default register look',
        'blue' => 'Blue — the classic line-of-business accent',
        'emerald' => 'Emerald — entry screens and confirmations',
        'amber' => 'Amber — warnings, ageing, exceptions',
        'rose' => 'Rose — variance and error-led reports',
        'violet' => 'Violet — vouchers and journals',
    ];

    private const DENSITIES = [
        'compact' => GridDensity::Compact,
        'normal' => GridDensity::Normal,
        'comfortable' => GridDensity::Comfortable,
    ];

    /** @var list<array<string, mixed>> */
    private const ROWS = [
        ['code' => 'RM-101', 'item' => 'Deluxe Room — Garden View', 'qty' => 12, 'rate' => '4500.00', 'amount' => '54000.00', 'state' => 'ok'],
        ['code' => 'RM-204', 'item' => 'Executive Suite', 'qty' => 4, 'rate' => '9800.00', 'amount' => '39200.00', 'state' => 'ok'],
        ['code' => 'FB-018', 'item' => 'Breakfast Buffet (per pax)', 'qty' => 32, 'rate' => '650.00', 'amount' => '20800.00', 'state' => 'watch'],
        ['code' => 'SP-007', 'item' => 'Spa — Aromatherapy 60 min', 'qty' => 6, 'rate' => '2750.00', 'amount' => '16500.00', 'state' => 'ok'],
        ['code' => 'TR-055', 'item' => 'Airport Transfer — Sedan', 'qty' => 9, 'rate' => '1900.00', 'amount' => '17100.00', 'state' => 'hold'],
    ];

    public function index(): View
    {
        return view('themes.index', [
            'themes' => self::THEMES,
            'themeGrids' => array_map(
                fn (string $theme): Grid => $this->sample('theme-'.$theme)->theme($theme),
                array_combine(array_keys(self::THEMES), array_keys(self::THEMES)),
            ),
            // A scheme the package has never heard of: two custom properties on a class.
            'brandGrid' => $this->sample('theme-brand')->themeClass('lgrid--theme-brand'),
            'densityGrids' => array_map(
                fn (GridDensity $density): Grid => $this->sample('density-'.$density->value)
                    ->theme('blue')->density($density),
                self::DENSITIES,
            ),
            'rows' => self::ROWS,
        ]);
    }

    /**
     * One definition, reused for every swatch — so the only difference between the grids
     * below really is the theme or the density.
     */
    private function sample(string $name): Grid
    {
        return Grid::make($name)
            ->toolbar(false)
            ->columns([
                SerialColumn::make(),
                TextColumn::make('code')->label('Code')->width(90),
                TextColumn::make('item')->label('Item')->sortable()->minWidth(220)->grow(),
                IntegerColumn::make('qty')->label('Qty')->sortable()->width(70)->align('right'),
                DecimalColumn::make('rate')->label('Rate')->scale(2)->sortable()
                    ->width(110)->align('right')->format('inr', ['scale' => 2]),
                DecimalColumn::make('amount')->label('Amount')->scale(2)->sortable()
                    ->width(120)->align('right')->format('inr', ['scale' => 2]),
                ComputedColumn::make('status')->label('Status')->html()->width(90)->align('center')
                    ->state(fn (array $row): string => match ($row['state'] ?? 'ok') {
                        'watch' => CellHtml::badge('amber', 'Watch'),
                        'hold' => CellHtml::badge('red', 'On hold'),
                        default => CellHtml::badge('green', 'Ready'),
                    }),
            ])
            ->footer([
                Aggregate::sum('qty')->format('number'),
                Aggregate::sum('amount')->format('inr', ['scale' => 2]),
            ])
            ->stickyHeader()
            ->striped()
            ->maxHeight('none');
    }
}
