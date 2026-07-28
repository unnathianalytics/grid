<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * Per-page SEO metadata for the LaraGrid demo site.
 *
 * Why a registry instead of per-page props: the demo mixes full-page Livewire components
 * (#[Layout] renders the layout for us, so there is no controller to pass data through)
 * with plain controller views. Resolving the metadata from the request path means one
 * layout reads one source and every page — whichever kind it is — gets a title, a
 * description, a keyword set, a canonical URL, Open Graph/Twitter cards and JSON-LD
 * without wiring anything at the call site.
 *
 * Keys are matched against the request path (Laravel's `request()->path()`, so '/' is '/'),
 * longest literal first, then a small wildcard pass for '{id}'-shaped routes.
 */
final class Seo
{
    public const SITE = 'LaraGrid';

    public const TAGLINE = 'Excel-style, keyboard-first datagrid for Laravel + Livewire';

    public const AUTHOR = 'Unnathi Analytics';

    public const PACKAGE = 'unnathianalytics/laragrid';

    public const REPO = 'https://github.com/unnathianalytics/laragrid';

    public const PACKAGIST = 'https://packagist.org/packages/unnathianalytics/laragrid';

    /**
     * Keywords every page carries — the head terms this site should rank for.
     *
     * @var list<string>
     */
    private const BASE_KEYWORDS = [
        'laragrid', 'laravel datagrid', 'livewire datagrid', 'laravel data grid',
        'laravel grid component', 'livewire table component', 'laravel table package',
        'excel like grid laravel', 'spreadsheet grid laravel', 'keyboard first datagrid',
        'editable grid livewire', 'laravel crud grid', 'php datagrid', 'laravel 13 grid',
        'livewire 4 datagrid', 'laravel admin table', 'data entry grid php',
    ];

    /**
     * The page table. Each entry: title (without the site suffix), description (≤160
     * chars, the snippet Google shows), keywords (page-specific, merged onto the base
     * set), heading + intro reused as on-page copy, and an optional social image.
     *
     * @var array<string, array<string, mixed>>
     */
    private const PAGES = [
        '/' => [
            'title' => 'LaraGrid — Excel-Style Datagrid for Laravel & Livewire',
            'title_short' => 'Home',
            'description' => 'LaraGrid is an Excel-style, keyboard-first datagrid for Laravel and Livewire: server-side sorting, search, filters, pagination, CSV/XLSX/PDF export, saved views, inline editing, formula columns and undo — installed with one composer require, zero JavaScript to write.',
            'keywords' => [
                'laravel livewire datagrid package', 'composer require laragrid',
                'laravel datagrid without javascript', 'laravel grid csv xlsx pdf export',
                'laravel grid saved views', 'laravel inline edit table',
                'laravel grid keyboard shortcuts', 'tally style data entry laravel',
                'laravel grid demo', 'free laravel datagrid',
            ],
            'image' => '/og/laragrid-table.png',
            'type' => 'website',
        ],
        'resorts' => [
            'title' => 'Server-Side Laravel Datagrid Demo — Sort, Filter, Export',
            'title_short' => 'Readonly grid',
            'description' => 'A live LaraGrid readonly demo over 500+ rows: whitelisted SQL sorting, debounced global search, toolbar and header filters, per-user saved views, bulk actions, frozen columns, grouped headers and CSV/XLSX/PDF export.',
            'keywords' => [
                'laravel server side pagination grid', 'livewire sortable table',
                'laravel table search filter', 'laravel grid export csv',
                'laravel grid export xlsx', 'laravel grid export pdf',
                'laravel saved views table', 'livewire bulk actions table',
                'laravel frozen columns grid', 'laravel grouped column headers',
                'laravel row actions table', 'laravel grid ternary filter',
            ],
            'image' => '/og/laragrid-table.png',
            'type' => 'article',
        ],
        'booking' => [
            'title' => 'Editable Livewire Datagrid Demo — Inline Edit & Formulas',
            'title_short' => 'Editable grid',
            'description' => 'A live LaraGrid editable demo: typed cell editors, an async search picker that enriches the row on select, live formula columns, conditional locking, host panels, auto-append, undo/redo and server-authoritative validation.',
            'keywords' => [
                'livewire editable table', 'laravel inline editing grid',
                'laravel formula column grid', 'laravel async select column',
                'livewire spreadsheet entry', 'laravel grid validation rules',
                'laravel grid undo redo', 'laravel grid paste tsv',
                'laravel data entry screen', 'livewire grid auto append rows',
            ],
            'image' => '/og/laragrid-form.png',
            'type' => 'article',
        ],
        'journal' => [
            'title' => 'Double-Entry Voucher Grid Demo — Balanced Entry, Dr/Cr',
            'title_short' => 'Voucher grid',
            'description' => 'A LaraGrid accounting demo: completeWhenBalanced() ends entry the moment debits equal credits, whenFilled/lockedWhen mirror a Dr/Cr selector, balancing autofill pre-fills the deficit side, and Enter carries focus straight to Save.',
            'keywords' => [
                'laravel accounting grid', 'double entry voucher laravel',
                'laravel journal entry screen', 'debit credit grid livewire',
                'laravel balanced entry form', 'tally like voucher entry php',
                'laravel ledger data entry', 'livewire accounting package',
            ],
            'image' => '/og/laragrid-form.png',
            'type' => 'article',
        ],
        'reports' => [
            'title' => 'Display-Only Grid Demo — Client Sort & What-If Totals',
            'title_short' => 'Display grid',
            'description' => 'A LaraGrid display-mode demo on a plain Blade page with no Livewire component: in-memory rows, client-side sorting, F9 what-if row hiding, footer aggregates and app-registered custom painters, formatters and column types.',
            'keywords' => [
                'laravel blade table component', 'laravel grid without livewire',
                'client side sorting table php', 'laravel report grid',
                'laravel trial balance table', 'custom cell renderer laravel grid',
                'laravel grid custom formatter', 'laravel grid custom column type',
                'what if totals grid',
            ],
            'image' => '/og/laragrid-table.png',
            'type' => 'article',
        ],
        'themes' => [
            'title' => 'LaraGrid Theming Demo — 6 Color Schemes & Dark Mode',
            'title_short' => 'Theming',
            'description' => 'Every LaraGrid theme side by side: the zinc, blue, emerald, amber, rose and violet presets, a custom two-token brand scheme, three row densities and a one-click dark-mode toggle — all pure CSS custom properties.',
            'keywords' => [
                'laravel grid theming', 'livewire table dark mode',
                'laravel grid css variables', 'tailwind laravel datagrid theme',
                'laravel grid color scheme', 'laravel grid density compact',
                'customise laravel table styles',
            ],
            'image' => '/og/laragrid-table.png',
            'type' => 'article',
        ],
        'resorts/create' => [
            'title' => 'LaraForm Demo — Keyboard-First Laravel Form Fields',
            'title_short' => 'LaraForm',
            'description' => 'The companion LaraForm demo: every field type on one keyboard-drivable Laravel form — text, money, date, select, async search select, multi-select, checkbox groups, Yes/No and toggles — with no Livewire and no page JavaScript.',
            'keywords' => [
                'laraform', 'laravel form package', 'keyboard first laravel form',
                'laravel form builder php', 'laravel form field types',
            ],
            'image' => '/og/laragrid-form.png',
            'type' => 'article',
        ],
    ];

    /**
     * @param  list<string>  $keywords
     */
    private function __construct(
        public readonly string $path,
        public readonly string $title,
        public readonly string $titleShort,
        public readonly string $description,
        public readonly array $keywords,
        public readonly string $image,
        public readonly string $type,
    ) {}

    /**
     * Resolve the metadata for a path (defaults to the current request).
     */
    public static function resolve(?string $path = null): self
    {
        $path = $path ?? request()->path();
        $page = self::PAGES[$path] ?? self::match($path) ?? self::PAGES['/'];

        /** @var list<string> $extra */
        $extra = $page['keywords'] ?? [];

        return new self(
            path: $path,
            title: (string) $page['title'],
            titleShort: (string) ($page['title_short'] ?? $page['title']),
            description: (string) $page['description'],
            keywords: array_values(array_unique([...self::BASE_KEYWORDS, ...$extra])),
            image: (string) ($page['image'] ?? '/og/laragrid-table.png'),
            type: (string) ($page['type'] ?? 'website'),
        );
    }

    /**
     * Wildcard fallback: `resorts/12/edit` reuses the `resorts/create` entry, since both
     * render the same form. Kept deliberately tiny — a full pattern engine would be more
     * machinery than a six-page demo can justify.
     */
    private static function match(string $path): ?array
    {
        if (preg_match('#^resorts/\d+/edit$#', $path) === 1) {
            return self::PAGES['resorts/create'];
        }

        return null;
    }

    /**
     * The <title> tag — the page title plus the site suffix, except on the home page
     * whose title already names the site.
     */
    public function documentTitle(): string
    {
        return $this->path === '/' ? $this->title : $this->title.' · '.self::SITE;
    }

    public function canonical(): string
    {
        return rtrim(url($this->path === '/' ? '/' : $this->path), '/') ?: url('/');
    }

    public function keywordList(): string
    {
        return implode(', ', $this->keywords);
    }

    /**
     * The page's schema.org graph: the software this site documents, the site itself, this
     * page, and (off the home page) a two-step breadcrumb trail. Built here rather than in
     * Blade because @json() cannot host a multi-line array literal.
     *
     * @return array<string, mixed>
     */
    public function jsonLd(): array
    {
        $graph = [
            [
                '@type' => 'SoftwareApplication',
                '@id' => url('/').'#software',
                'name' => self::SITE,
                'alternateName' => self::PACKAGE,
                'applicationCategory' => 'DeveloperApplication',
                'applicationSubCategory' => 'Laravel package',
                'operatingSystem' => 'Any (PHP 8.1+)',
                'description' => self::TAGLINE.'. Server-side sorting, search, filters, pagination, CSV/XLSX/PDF export, saved views, inline editing, formula columns, undo/redo and full keyboard control.',
                'url' => url('/'),
                'downloadUrl' => self::PACKAGIST,
                'codeRepository' => self::REPO,
                'programmingLanguage' => ['PHP', 'JavaScript'],
                'license' => 'https://opensource.org/licenses/MIT',
                'author' => ['@type' => 'Organization', 'name' => self::AUTHOR],
                'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
                'softwareRequirements' => 'PHP ^8.1, Laravel 10/11/12/13, Livewire ^4.1',
                'keywords' => $this->keywordList(),
            ],
            [
                '@type' => 'WebSite',
                '@id' => url('/').'#website',
                'name' => self::SITE,
                'url' => url('/'),
                'description' => self::TAGLINE,
                'inLanguage' => 'en',
                'publisher' => ['@type' => 'Organization', 'name' => self::AUTHOR],
            ],
            [
                '@type' => 'WebPage',
                '@id' => $this->canonical().'#webpage',
                'url' => $this->canonical(),
                'name' => $this->title,
                'description' => $this->description,
                'isPartOf' => ['@id' => url('/').'#website'],
                'about' => ['@id' => url('/').'#software'],
                'primaryImageOfPage' => url($this->image),
                'inLanguage' => 'en',
            ],
        ];

        if ($this->path !== '/') {
            $graph[] = [
                '@type' => 'BreadcrumbList',
                '@id' => $this->canonical().'#breadcrumb',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => self::SITE, 'item' => url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $this->titleShort, 'item' => $this->canonical()],
                ],
            ];
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }

    /**
     * The home page's FAQ block — real questions developers type into a search box, answered
     * on the page itself. Emitted as FAQPage structured data and rendered as visible copy;
     * both halves must stay in sync, so they read from this one list.
     *
     * @return list<array{q: string, a: string}>
     */
    public static function faqs(): array
    {
        return [
            [
                'q' => 'What is LaraGrid?',
                'a' => 'LaraGrid is an Excel-style, keyboard-first datagrid for Laravel and Livewire. It ships three modes — a display grid for in-memory rows, a readonly server-side register with sorting, search, filters, pagination and exports, and a fully editable entry grid with typed cell editors, formula columns and undo — all configured with chained methods in your component class.',
            ],
            [
                'q' => 'How do I install LaraGrid in a Laravel project?',
                'a' => 'Run composer require unnathianalytics/laragrid. The service provider auto-discovers and the prebuilt script and stylesheet auto-inject into any page that renders a grid, so there is no layout directive, no npm step and no build. Run php artisan migrate once only if you use saved views.',
            ],
            [
                'q' => 'Does LaraGrid require writing JavaScript?',
                'a' => 'No. The grid engine is framework-free vanilla JavaScript shipped inside the package. Every behaviour — columns, sorting, filters, validation, formulas, actions, exports, theming — is declared in PHP on the Grid definition. Custom painters, editors, formatters and casts can be registered through window.LaraGrid when an app wants to extend it.',
            ],
            [
                'q' => 'Which Laravel and Livewire versions does LaraGrid support?',
                'a' => 'PHP 8.1 or newer, Laravel 10, 11, 12 or 13, and Livewire 4.1 or newer, which is installed automatically as a dependency.',
            ],
            [
                'q' => 'Can LaraGrid export a table to CSV, Excel or PDF?',
                'a' => 'Yes. A readonly grid that declares exportable() gains a toolbar Export control that downloads the operator\'s current view — active sort, global search and filters applied over the whole filtered set. All three writers are dependency-free: CSV as BOM-ed UTF-8, XLSX as native SpreadsheetML with typed number cells, and a native A4 PDF writer.',
            ],
            [
                'q' => 'How does inline editing stay safe if the client applies keystrokes optimistically?',
                'a' => 'The client paints every keystroke immediately and streams typed ops to the server, where each write is authorized, cast, validated, run through your hooks and recomputed for formula columns. The response reconciles the authoritative values back into the grid, so an optimistic value can never outlive a rule that refuses it.',
            ],
            [
                'q' => 'Is LaraGrid keyboard accessible?',
                'a' => 'The keyboard is the primary interface. The grid is a single tab stop with a roving active cell exposed through aria-activedescendant, arrows, Tab, Home, End and Page keys navigate, Ctrl+C copies the selection as TSV, Enter drives a context-aware serpentine entry flow, and Ctrl+Z / Ctrl+Y undo and redo. Two presets ship: entry and excel.',
            ],
            [
                'q' => 'How much does LaraGrid cost?',
                'a' => 'LaraGrid is free and open source under the MIT license.',
            ],
        ];
    }

    /**
     * Every indexable page, for the sitemap.
     *
     * @return list<array{path: string, priority: string, changefreq: string}>
     */
    public static function sitemap(): array
    {
        $priorities = ['/' => '1.0'];

        return array_values(array_map(fn (string $path): array => [
            'path' => $path,
            'priority' => $priorities[$path] ?? '0.8',
            'changefreq' => $path === '/' ? 'weekly' : 'monthly',
        ], array_keys(self::PAGES)));
    }

    /**
     * The nav — the demo pages in reading order, each with the label the header shows and
     * the `title` attribute search engines and screen readers both benefit from.
     *
     * @return list<array{href: string, label: string, title: string, active: bool}>
     */
    public static function nav(): array
    {
        $links = [
            '/' => ['Overview', 'LaraGrid overview — features, install and keyboard model'],
            'resorts' => ['Readonly Grid', 'Server-side datagrid: sort, search, filter, export, saved views'],
            'booking' => ['Editable Grid', 'Editable datagrid: inline editing, formulas, async pickers'],
            'journal' => ['Voucher Grid', 'Double-entry voucher grid: balanced completion and Dr/Cr locking'],
            'reports' => ['Display Grid', 'Display-only grid: client-side sort, what-if totals, custom cells'],
            'themes' => ['Theming', 'Six shipped color schemes, dark mode and CSS tokens'],
            'resorts/create' => ['LaraForm', 'The companion keyboard-first Laravel form package'],
        ];

        $current = request()->path();

        return array_values(array_map(fn (string $path) => [
            'href' => $path === '/' ? '/' : '/'.$path,
            'label' => $links[$path][0],
            'title' => $links[$path][1],
            'active' => $current === $path
                || ($path === 'resorts/create' && Route::is('resorts.edit')),
        ], array_keys($links)));
    }
}
