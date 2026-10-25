<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Resort;
use Illuminate\Contracts\View\View;
use LaraGrid\Actions\Action;
use LaraGrid\Aggregate;
use LaraGrid\ColumnGroup;
use LaraGrid\Columns\CheckboxColumn;
use LaraGrid\Columns\ComputedColumn;
use LaraGrid\Columns\DateColumn;
use LaraGrid\Columns\DecimalColumn;
use LaraGrid\Columns\HiddenColumn;
use LaraGrid\Columns\IntegerColumn;
use LaraGrid\Columns\ReadonlyColumn;
use LaraGrid\Columns\SelectColumn;
use LaraGrid\Columns\SerialColumn;
use LaraGrid\Columns\TextColumn;
use LaraGrid\Columns\YesNoColumn;
use LaraGrid\Filters\SelectFilter;
use LaraGrid\Filters\TernaryFilter;
use LaraGrid\Grid;
use LaraGrid\GridDensity;
use LaraGrid\Livewire\WithLaraGrid;
use LaraGrid\Support\CellHtml;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The READONLY server-side showcase — every option a ->query() grid can declare, on one
 * definition, over the real 500-row resorts table.
 *
 * Covered here (the readonly half of the LaraGrid surface):
 *   data        — query(), authorize(), rowKey(), paginate(), singlePageUpTo(), defaultSort(),
 *                 searchable() (declared columns + a dot-qualified DB column), filters()
 *                 (SelectFilter + TernaryFilter), column-level filterable() header funnels,
 *                 exportable(formats, fileName:, limit:), rowActivate(navigate: true)
 *   persistence — three different lifetimes that compose on one grid:
 *                 persistQuery()  search / filters / sort / per-page, Laravel session, automatic
 *                 persistWidths() column widths + hidden columns, localStorage, automatic
 *                 savedViews()    all of the above by name, laragrid_views table, on demand
 *   columns     — Serial, Integer, Text, Select, Checkbox, YesNo, Decimal, Date, Computed
 *                 (+ ->html() badges and edit links), Readonly and Hidden (carried, unpainted),
 *                 with label/width/minWidth/maxWidth/grow/align/format/frozen/sortable/
 *                 searchable/filterable/exportable(false)/resizable(false) — and the two kinds
 *                 of "not shown": hiddenByDefault() (the chooser can reveal it) vs
 *                 visible(false) (operators never can)
 *   actions     — row url() + call() actions with confirm/visible/authorize, bulk actions over
 *                 checked rows, and toolbar actions
 *   host seams  — refreshGrid() (lgrid:refresh: re-fetch the current view after an out-of-band
 *                 change) and forgetGridQuery() (drop the persisted query on demand)
 *   layout      — columnGroups(), stickyHeader(), freezeColumns(), striped(), density(),
 *                 theme(), height caps, rowClass(), cellClass(), statusBar(), toolbar() tuning,
 *                 emptyState(), footer aggregates
 *
 * Everything is declared in this class: no Blade wiring, no JavaScript, no build step.
 */
#[Layout('components.layouts.app', ['wide' => true])]
class ResortsIndex extends Component
{
    use WithLaraGrid;

    /** Option maps shared by the select columns and their header funnels. */
    private const TYPES = [
        'Homestay' => 'Homestay', 'Resort' => 'Resort', 'Hotel' => 'Hotel',
        'Guest House' => 'Guest House', 'Package' => 'Package', 'Activities' => 'Activities',
        'Hospitality' => 'Hospitality', 'Star Category' => 'Star Category',
    ];

    private const STARS = ['3' => '3 Star', '4' => '4 Star', '5' => '5 Star', 'unrated' => 'Unrated'];

    /**
     * @return array<string, Grid>
     */
    protected function grids(): array
    {
        return [
            'resorts' => Grid::make('resorts')
                // is_visible: a derived 1/0 the YesNoColumn below paints as Y/N — typed value
                // columns read a real row value, so derivations belong in the query (unlike
                // ComputedColumn, whose ->state() bakes values row-by-row after the query).
                ->query(fn () => Resort::query()
                    ->selectRaw("resorts.*, (COALESCE(visibility, 'show') = 'show') as is_visible"))
                // Demo app has no auth/policies — permit openly. Gate with a policy in real apps.
                ->authorize(fn (): bool => true)
                // The primary key every op, action and selection addresses rows by. Defaults to
                // 'id'; declared here because a register keyed on anything else silently breaks.
                ->rowKey('id')
                ->paginate(15, [10, 15, 25, 50, 100])
                // Adaptive single page: whenever the FILTERED set fits in 200 rows the grid
                // serves it whole and drops the pagination chrome — narrow the search and watch
                // the pager disappear. Decided per request, server-side.
                ->singlePageUpTo(200)
                ->defaultSort('name')
                // Bare names must be DECLARED columns (a typo fails at build time); a
                // dot-qualified target is an explicit DB column the grid never paints —
                // that is how 'resorts.slug' joins the search surface without a column.
                ->searchable(['name', 'shortcode', 'city', 'manager', 'resorts.slug'])
                // Grid-level filters render as toolbar controls. Column-level ->filterable()
                // filters (see city / star_rating below) render as header funnels instead —
                // both run through the same whitelisted, bound-parameter pipeline.
                ->filters([
                    SelectFilter::make('type')->label('Type')->options(self::TYPES),
                    SelectFilter::make('visibility')->label('Visibility')
                        ->options(['show' => 'Show', 'hide' => 'Hide']),
                    TernaryFilter::make('featured')->label('Featured'),
                    TernaryFilter::make('gst_applicable')->label('GST'),
                ])
                ->columnGroups([
                    ColumnGroup::make('Identity', ['id', 'name', 'shortcode', 'type']),
                    ColumnGroup::make('Location', ['city', 'star_rating']),
                    ColumnGroup::make('Commercials', ['comparison_tariff', 'room_rate', 'rooms', 'hits']),
                    ColumnGroup::make('Contact', ['manager', 'contact_phone', 'contact_email']),
                    ColumnGroup::make('Status', ['is_visible', 'gst_applicable', 'featured', 'status', 'edit']),
                    ColumnGroup::make('Dates', ['opened_on', 'created_at']),
                ])
                ->columns([
                    SerialColumn::make(),
                    IntegerColumn::make('id')->label('ID')->sortable()->width(64)->align('right'),
                    TextColumn::make('name')->label('Resort')->sortable()->searchable()
                        ->minWidth(200)->grow(),
                    TextColumn::make('shortcode')->label('Code')->sortable()->searchable()
                        ->width(90)->resizable(false),
                    // A SelectColumn paints the LABEL for a stored id from its embedded
                    // whitelist — in a readonly grid that is a pure value-to-label map.
                    SelectColumn::make('type')->label('Type')->options(self::TYPES)
                        ->sortable()->width(120),
                    // ->filterable() attaches the filter to this column's HEADER FUNNEL rather
                    // than the toolbar. Same pipeline, different chrome.
                    TextColumn::make('city')->label('City')->sortable()->searchable()->width(120)
                        ->filterable(SelectFilter::make('city')->label('City')
                            ->options(fn () => Resort::query()->whereNotNull('city')
                                ->distinct()->orderBy('city')->pluck('city', 'city'))),
                    SelectColumn::make('star_rating')->label('Stars')->options(self::STARS)
                        ->sortable()->width(96)->align('center')
                        ->filterable(SelectFilter::make('star_rating')->label('Stars')->options(self::STARS)),
                    IntegerColumn::make('comparison_tariff')->label('Tariff')->sortable()
                        ->width(100)->align('right')->format('number'),
                    // Fixed-scale money: the value never rides a float, and ->format() decides
                    // only how it is PAINTED (CSV/XLSX still export the raw number).
                    DecimalColumn::make('room_rate')->label('Rack Rate')->scale(2)->sortable()
                        ->width(110)->align('right')->format('number', ['scale' => 2]),
                    IntegerColumn::make('rooms')->label('Rooms')->sortable()->width(80)->align('right'),
                    IntegerColumn::make('hits')->label('Hits')->sortable()->width(80)->align('right')
                        ->format('number'),
                    // ReadonlyColumn: painted, never writable — the explicit way to carry a
                    // display-only value into an otherwise editable definition.
                    ReadonlyColumn::make('manager')->label('Manager')->width(130),
                    // visible(false): a DEFINITION-level hidden column. It is not listed in the
                    // column chooser, so no operator can ever reveal it — the right tool for a
                    // value the host wants carried but never shown.
                    TextColumn::make('contact_phone')->label('Phone')->width(150)->visible(false),
                    // hiddenByDefault(): starts unchecked in the column chooser (▦) but stays
                    // available there — the optional column an operator may switch on. It is
                    // also what ->persistWidths() and saved views remember as "hidden".
                    TextColumn::make('contact_email')->label('Email')->width(200)->hiddenByDefault(),
                    YesNoColumn::make('is_visible')->label('Live?')->width(80)->align('center'),
                    YesNoColumn::make('gst_applicable')->label('GST?')->width(80)->align('center'),
                    CheckboxColumn::make('featured')->label('Featured')->width(90)->align('center'),
                    // ->html() cells paint server-composed markup; CellHtml is the XSS-safe
                    // fragment factory (badges, edit links, the muted em-dash placeholder).
                    ComputedColumn::make('status')->label('Visibility')->html()->width(96)
                        ->state(fn (array $row): string => ($row['visibility'] ?? 'show') === 'show'
                            ? CellHtml::badge('green', 'Show')
                            : CellHtml::badge('zinc', 'Hide')),
                    ComputedColumn::make('edit')->label('Open')->html()->width(90)->align('center')
                        // Painted chrome has no business in a download.
                        ->exportable(false)
                        ->state(fn (array $row): string => isset($row['id'])
                            ? CellHtml::editLink(route('resorts.edit', $row['id']))
                            : CellHtml::muted()),
                    DateColumn::make('opened_on')->label('Opened')->sortable()->width(110)
                        ->displayFormat('d M Y'),
                    DateColumn::make('created_at')->label('Added')->sortable()->width(110),
                    // A HiddenColumn is never painted — it simply travels with the row so
                    // actions, exports and host code can read it.
                    HiddenColumn::make('slug'),
                ])
                // v1 aggregates are sums; the selection status bar below adds live
                // Count / Sum / Average over whatever range the operator highlights.
                ->footer([
                    Aggregate::sum('rooms')->format('number'),
                    Aggregate::sum('comparison_tariff')->format('number'),
                    Aggregate::sum('room_rate')->format('number', ['scale' => 2]),
                    Aggregate::sum('hits')->format('number'),
                ])
                // The download is always the operator's CURRENT view — active sort, search and
                // filters, the whole filtered set rather than the visible page.
                ->exportable(['csv', 'xlsx', 'pdf'], fileName: 'resorts-register', limit: 20000)
                // Named, per-user, server-persisted snapshots of search + filters + sort +
                // per-page + column layout, recalled from the ❖ Views toolbar menu.
                ->savedViews()
                // Sticky filters: the CURRENT search, filters, sort and per-page survive for the
                // rest of the session (Laravel session, key laragrid.query.resorts). Narrow the
                // list, open a resort, come back — the list is still narrowed, and it was built
                // narrowed ON THE SERVER, so there is no unfiltered first paint. The page number
                // is deliberately not kept: a return lands on page 1 of your filtering. Stale
                // values drop themselves (a filter value its own options no longer accept is
                // skipped), and startClean() below shows the host-side forget.
                ->persistQuery()
                // Enter or double-click on a row opens it — the same gesture, one declaration.
                // The URL is resolved server-side per row (null = no affordance). With
                // navigate: true the grid itself follows it (Livewire Navigate when the app runs
                // it, a full load otherwise); without it, activation only dispatches
                // lgrid:activate and the host decides — a modal, a drill-down, a custom action.
                ->rowActivate(fn (array $row): ?string => isset($row['id'])
                    ? route('resorts.edit', $row['id'])
                    : null, navigate: true)
                ->actions([
                    Action::make('edit')->label('Edit')->icon('✎')
                        ->url(fn (array $row): string => route('resorts.edit', $row['id'])),
                    Action::make('toggle')->label('Show / Hide')->icon('👁')
                        // Per-action gate, re-checked server-side before the closure runs.
                        ->authorize(fn (): bool => true)
                        ->call(function (array $row): void {
                            Resort::whereKey($row['id'])->update([
                                'visibility' => ($row['visibility'] ?? 'show') === 'show' ? 'hide' : 'show',
                            ]);
                        }),
                    // Two mutually exclusive buttons behind ->visible(): a hidden button is an
                    // unusable button — the server re-evaluates the predicate before running.
                    Action::make('feature')->label('Feature')->icon('★')
                        ->visible(fn (array $row): bool => ! ($row['featured'] ?? false))
                        ->call(fn (array $row) => Resort::whereKey($row['id'])->update(['featured' => true])),
                    Action::make('unfeature')->label('Un-feature')->icon('☆')
                        ->visible(fn (array $row): bool => (bool) ($row['featured'] ?? false))
                        ->call(fn (array $row) => Resort::whereKey($row['id'])->update(['featured' => false])),
                ])
                ->bulkActions([
                    Action::make('hide-selected')->label('Hide')->icon('🙈')
                        ->confirm('Hide all selected resorts?')
                        ->call(fn (array $keys) => Resort::whereKey($keys)->update(['visibility' => 'hide'])),
                    Action::make('show-selected')->label('Show')->icon('👀')
                        ->call(fn (array $keys) => Resort::whereKey($keys)->update(['visibility' => 'show'])),
                    Action::make('feature-selected')->label('Feature')->icon('★')
                        ->confirm('Mark every selected resort as featured?')
                        ->call(fn (array $keys) => Resort::whereKey($keys)->update(['featured' => true])),
                ])
                ->toolbarActions([
                    Action::make('new')->label('＋ New Resort')
                        ->url(fn (): string => route('resorts.create')),
                ])
                // Layout & chrome.
                ->stickyHeader()
                ->freezeColumns(3)          // gutter + ID + Resort stay put while you scroll right
                ->striped()
                ->density(GridDensity::Compact)
                ->theme('blue')             // zinc · blue · emerald · amber · rose · violet
                ->persistWidths()           // column widths + hidden columns survive a reload
                ->statusBar()               // live Count / Sum / Average over the selection
                ->toolbar(search: true, filters: true, perPage: true, chooser: true)
                ->maxHeight('68vh')
                ->rowClass(fn (array $row): ?string => ($row['featured'] ?? false) ? 'row-featured' : null)
                ->cellClass(fn (mixed $value, array $row, string $column): ?string => $column === 'hits' && (int) $value > 5000
                    ? 'cell-hot'
                    : null)
                ->emptyState('No resorts match this search.'),
        ];
    }

    /**
     * Host seam #1 — drop the persisted query. ->persistQuery() remembers the operator's
     * narrowing for the session; the events that should END it (a tenant switch, a "start
     * clean" control) are the host's to recognise, so the trait exposes forgetGridQuery().
     * A no-op on a grid that never declared ->persistQuery(). The full reload that follows
     * paints the declared defaults again: no search, no filters, default sort, page 1.
     */
    public function startClean(): void
    {
        $this->forgetGridQuery('resorts');

        $this->redirect(route('resorts.index'));
    }

    /**
     * Host seam #2 — ask the grid to re-fetch its current view, cache-busted (lgrid:refresh).
     * The CRUD companion for a modal create/edit that changed rows behind the grid's back:
     * no remount, no wire:key tricks. (The grid's own call() actions already refetch after
     * they run; this is for changes the grid did not make.)
     */
    public function refresh(): void
    {
        $this->refreshGrid('resorts');
    }

    public function render(): View
    {
        return view('livewire.resorts-index');
    }
}
