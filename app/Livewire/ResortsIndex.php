<?php

namespace App\Livewire;

use App\Models\Resort;
use Illuminate\Contracts\View\View;
use LaraGrid\Actions\Action;
use LaraGrid\Aggregate;
use LaraGrid\Columns\ComputedColumn;
use LaraGrid\Columns\DateColumn;
use LaraGrid\Columns\IntegerColumn;
use LaraGrid\Columns\SerialColumn;
use LaraGrid\Columns\TextColumn;
use LaraGrid\Filters\SelectFilter;
use LaraGrid\Grid;
use LaraGrid\Livewire\WithLaraGrid;
use LaraGrid\Support\CellHtml;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * All resorts — server-side readonly LaraGrid over the travelchords resorts table.
 *
 * Showcases: whitelisted sort/search, toolbar filters (type, visibility), footer sum,
 * a badge via an ->html() computed column, a row call-action (visibility toggle) and a
 * bulk hide with confirm — all declared here, zero blade wiring.
 */
#[Title('Resorts')]
#[Layout('components.layouts.app')]
class ResortsIndex extends Component
{
    use WithLaraGrid;

    /**
     * @return array<string, Grid>
     */
    protected function grids(): array
    {
        return [
            'resorts' => Grid::make('resorts')
                ->query(fn () => Resort::query())
                // Demo app has no auth/policies — permit openly. Gate with a policy in real apps.
                ->authorize(fn (): bool => true)
                ->paginate(25, [10, 25, 50, 100])
                ->defaultSort('name')
                // name + shortcode are declared columns; slug/address are searched as raw DB
                // columns via the dot-qualified form (undeclared bare names are rejected —
                // the whitelist that keeps the search surface injection-closed).
                ->searchable(['name', 'shortcode', 'resorts.slug', 'resorts.address'])
                ->filters([
                    SelectFilter::make('type')->label('Type')
                        ->options(fn () => Resort::query()
                            ->whereNotNull('type')
                            ->distinct()->orderBy('type')
                            ->pluck('type', 'type')),
                    SelectFilter::make('visibility')->label('Visibility')
                        ->options(['show' => 'Show', 'hide' => 'Hide']),
                ])
                ->columns([
                    SerialColumn::make(),
                    IntegerColumn::make('id')->label('ID')->sortable()->width(70),
                    TextColumn::make('name')->label('Resort')->sortable()->searchable()->grow(),
                    TextColumn::make('type')->label('Type')->sortable()->width(120),
                    TextColumn::make('shortcode')->label('Code')->searchable()->width(110),
                    TextColumn::make('rating')->label('Rating')->align('right')->sortable()->width(80),
                    IntegerColumn::make('comparison_tariff')->label('Tariff')->sortable()->width(100),
                    IntegerColumn::make('hits')->label('Hits')->sortable()->width(90),
                    ComputedColumn::make('status')->label('Status')->html()->width(90)
                        ->state(fn (array $row): string => ($row['visibility'] ?? 'show') === 'show'
                            ? CellHtml::badge('green', 'Show')
                            : CellHtml::badge('zinc', 'Hide')),
                    DateColumn::make('created_at')->label('Added')->sortable()->width(110),
                ])
                ->footer([
                    Aggregate::sum('hits')->format('number'),
                ])
                ->actions([
                    Action::make('toggle')->label('Show / Hide')->icon('👁')
                        ->call(function (array $row): void {
                            Resort::whereKey($row['id'])->update([
                                'visibility' => ($row['visibility'] ?? 'show') === 'show' ? 'hide' : 'show',
                            ]);
                        }),
                ])
                ->bulkActions([
                    Action::make('hide-selected')->label('Hide')->icon('🙈')
                        ->confirm('Hide all selected resorts?')
                        ->call(fn (array $keys) => Resort::whereKey($keys)->update(['visibility' => 'hide'])),
                ])
                ->stickyHeader()
                ->striped()
                ->maxHeight('70vh')
                ->emptyState('No resorts found.'),
        ];
    }

    public function render(): View
    {
        return view('livewire.resorts-index');
    }
}
