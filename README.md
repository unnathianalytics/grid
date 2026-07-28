# LaraGrid demo — grid.laravel.cloud

The live demo site for **[LaraGrid](https://github.com/unnathianalytics/laragrid)**, an
Excel-style, keyboard-first datagrid for **Laravel + Livewire**, and its companion form
package **LaraForm**.

Every page here is a working component, not a screenshot: the same 500-row table drives a
readonly server-side register, two editable entry grids, a display-only report page, and a
theming gallery. Each page ships its own source viewer, so what you read is what is running.

**Live → [grid.laravel.cloud](https://grid.laravel.cloud/)**

## The pages

| Route | Mode | What it exercises |
|---|---|---|
| `/` | display | Overview, install, the three modes, the keyboard reference, FAQs, and a live teaser grid |
| `/resorts` | readonly, server-side | `query()`, `authorize()`, `paginate()`, `singlePageUpTo()`, `searchable()`, toolbar `filters()` + column `filterable()` funnels, `exportable()` (CSV/XLSX/PDF), `savedViews()`, `rowActivate()`, row / bulk / toolbar actions, grouped headers, frozen columns, `rowClass()` / `cellClass()`, `persistWidths()` |
| `/booking` | editable | Async picker with `onSelect()` enrichment, chained formula columns, `rules()` / `required(fn)` / `readonly(fn)` / `requiredWhen()` / `lockedWhen()` / `whenFilled()`, `afterCellChange()` / `afterRowRemove()`, `autoAppend()`, `endOfListOption()`, `opensPanel()` + `gridPanelDone()`, `refreshesHost()`, `focusOutTo()` vs `onCompleteFocus()` |
| `/journal` | editable | `completeWhenBalanced()` with balancing autofill, a mutually exclusive Dr/Cr pair, `SyncPolicy::PerRow`, an `optionsUsing()` picker over a plain PHP array |
| `/reports` | display | In-memory rows on a plain Blade page (no Livewire), client-side sorting, <kbd>F9</kbd> what-if row hiding, `toolbar(false)`, and the extension seams — a custom column type, painter, format and parse kind |
| `/themes` | display | All six shipped color schemes, a custom two-token scheme, three densities, dark mode, and the `--lgrid-*` token surface |
| `/resorts/create` | — | The LaraForm demo: every field type on one keyboard-drivable form |

## Running it locally

```bash
composer setup          # install, key, migrate, npm install, npm run build
php artisan db:seed --class=ResortDemoSeeder   # backfill the demo-only columns
composer dev            # serve + queue + logs + vite
```

The database is SQLite (`database/database.sqlite`), committed with the resorts table already
populated. `ResortDemoSeeder` fills the columns the grid demos paint (city, star rating, rooms,
rack rate, manager, contact details, opening dates) deterministically from each row's id, so
re-running it is idempotent.

## How the demo is put together

- **`app/Livewire/*`** — the three Livewire grid pages. Each grid is one chained `Grid::make()`
  expression; there is no Blade wiring and no page JavaScript.
- **`app/Http/Controllers/{Home,Reports,Themes}Controller.php`** — the display-mode pages,
  which need no Livewire component at all.
- **`app/Grid/*`** — this app's LaraGrid extensions: a custom `RatingColumn`, an `inr` display
  format and a `stars` parse kind, registered in `AppServiceProvider` with their JavaScript
  twins in `resources/views/partials/laragrid-extensions.blade.php`.
- **`app/Support/Seo.php`** — the per-page SEO registry (title, description, keywords,
  canonical, Open Graph / Twitter cards, JSON-LD) the layout reads for every page, Livewire and
  controller alike. `/sitemap.xml` and `/robots.txt` are generated from the same table.
- **`resources/views/components/source-code.blade.php`** — the on-page source viewer.

## Licence

MIT. LaraGrid and LaraForm are © Unnathi Analytics.
