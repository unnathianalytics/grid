@php use App\Support\Seo; @endphp

<x-layouts.app :wide="true">
    <header class="hero">
        <h1>LaraGrid — an Excel-style, keyboard-first datagrid for Laravel&nbsp;+&nbsp;Livewire</h1>

        <p class="lede">
            LaraGrid is a <strong>Laravel datagrid package</strong> extracted from a production
            accounting system built for spreadsheet-trained operators, then made app-neutral.
            The engine is framework-free vanilla JavaScript that owns every cell it paints: the
            grid body lives inside a <code>wire:ignore</code> region, Livewire never morphs a
            row, and all server traffic runs over renderless RPCs. The result is
            spreadsheet-grade speed with Laravel-grade authority — every edit is validated,
            authorized and recomputed server-side.
        </p>

        <p class="lede">
            Everything is configured <strong>in your component class with chained methods</strong>.
            No Blade wiring, no JavaScript to write, no npm step —
            <code>composer require</code> is the entire install.
        </p>

        <pre class="snippet"><code>composer require {{ Seo::PACKAGE }}</code></pre>

        <p class="muted">
            PHP 8.1+ · Laravel 10 / 11 / 12 / 13 · Livewire 4.1+ (installed automatically) ·
            MIT licensed · <a href="{{ Seo::PACKAGIST }}" rel="noopener nofollow">Packagist</a> ·
            <a href="{{ Seo::REPO }}" rel="noopener nofollow">GitHub</a>
        </p>
    </header>

    <h2>See it working</h2>
    <p class="lede">
        The eight most-viewed properties from this demo's {{ number_format($resortCount) }}-row
        table. Click a header to sort, drag a selection and read Count / Sum / Average off the
        status bar, then press <kbd>Ctrl</kbd>+<kbd>C</kbd> and paste straight into Excel.
    </p>

    <x-laragrid :grid="$grid" :rows="$rows" />

    <h2>The three modes</h2>
    <div class="table-wrap">
        <table class="doc">
            <thead>
                <tr><th>Mode</th><th>Declare with</th><th>What you get</th><th>Demo</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Display</strong></td>
                    <td><code>rows passed to the tag</code></td>
                    <td>Paints in-memory rows on a plain Blade page with no Livewire component.
                        <code>sortable()</code> columns sort client-side — stable, type-aware,
                        empties last. Built for computed report grids (trial balance, ageing)
                        that can never be <code>query()</code>-backed.</td>
                    <td><a href="/reports">Display grid →</a></td>
                </tr>
                <tr>
                    <td><strong>Readonly, server-side</strong></td>
                    <td><code>-&gt;query(fn () =&gt; Model::query())</code></td>
                    <td>Sorting, global search, filters and pagination through a whitelisted
                        fail-closed pipeline. Page&nbsp;1 ships in the initial payload for a
                        zero-round-trip first paint; later pages stream over an RPC with an LRU
                        cache and idle prefetch. Opt-in CSV/XLSX/PDF export and per-user saved
                        views.</td>
                    <td><a href="/resorts">Readonly grid →</a></td>
                </tr>
                <tr>
                    <td><strong>Editable</strong></td>
                    <td><code>-&gt;editable()-&gt;rowsFrom('lines')</code></td>
                    <td>The full spreadsheet: optimistic client, authoritative server, a typed
                        op protocol, validation on both sides, formula columns, async pickers
                        with row enrichment, auto-append, undo/redo and live footer totals.</td>
                    <td><a href="/booking">Editable grid →</a> ·
                        <a href="/journal">Voucher grid →</a></td>
                </tr>
            </tbody>
        </table>
    </div>

    <h2>What a Laravel datagrid should do out of the box</h2>

    <div class="cards">
        <article>
            <h3>Server-side sorting, search &amp; filters</h3>
            <p>Declare <code>searchable()</code>, <code>filters()</code> and
               <code>sortable()</code> and the toolbar renders itself. Every narrowing runs
               through a whitelisted, bound-parameter SQL pipeline that is fail-closed by
               construction — nothing the browser sends can widen the query.</p>
        </article>
        <article>
            <h3>Pagination that adapts</h3>
            <p><code>paginate()</code> with a per-page picker, plus
               <code>singlePageUpTo(N)</code>: whenever the filtered set fits, the grid serves
               it whole and drops the pager. Oversized first pages defer to a post-boot fetch,
               so mount HTML stays small at any table size.</p>
        </article>
        <article>
            <h3>CSV, Excel and PDF export</h3>
            <p><code>exportable()</code> downloads the operator's current view — active sort,
               search and filters over the whole filtered set. All three writers are
               dependency-free: BOM-ed UTF-8 CSV, native SpreadsheetML XLSX with typed number
               cells, and a native A4 PDF writer. Register your own for anything else.</p>
        </article>
        <article>
            <h3>Per-user saved views</h3>
            <p><code>savedViews()</code> persists named snapshots of search + filters + sort +
               per-page + column layout, server-side and scoped to the authenticated operator.
               Views are sanitized against the grid's declared surface, so one operator can
               never see, apply or delete another's.</p>
        </article>
        <article>
            <h3>Inline editing with a real contract</h3>
            <p>The client paints every keystroke and streams typed ops; the server authorizes,
               casts, validates, runs your hooks and recomputes formulas, then reconciles the
               authoritative values back. Rows are addressed by stable keys, never positions.</p>
        </article>
        <article>
            <h3>Formula columns in two runtimes</h3>
            <p><code>FormulaColumn::make('amount')-&gt;formula('round(qty * rate, 2)')</code> —
               evaluated live in the browser for instant feedback and authoritatively in PHP by
               a twin evaluator, pinned to the same committed vectors.</p>
        </article>
        <article>
            <h3>Async pickers that enrich the row</h3>
            <p><code>SearchSelectColumn</code> streams options over an RPC and
               <code>onSelect()</code> pre-fills dependent cells server-side; formula columns
               recompute after the hook, so one pick updates the whole row in a single round
               trip.</p>
        </article>
        <article>
            <h3>Undo, redo and bulk paste</h3>
            <p>100 steps of history where one gesture is one step — a 200-cell paste, a
               fill-down, a row delete. Undo replays through the same op protocol as typing, so
               it can never resurrect a value your rules would refuse.</p>
        </article>
        <article>
            <h3>Actions, fail-closed end to end</h3>
            <p>Row, bulk and toolbar actions. The client echoes only an action name; the server
               re-authorizes the grid gate and the action gate, re-resolves the row from its
               authoritative source, and re-checks <code>visible()</code> before your closure
               runs.</p>
        </article>
        <article>
            <h3>Theming with CSS tokens</h3>
            <p>Six shipped schemes with coordinated dark variants, three densities, and every
               visual exposed as a <code>--lgrid-*</code> custom property with a self-contained
               default. In a Tailwind v4 app it adopts your <code>@theme</code> palette
               automatically.</p>
        </article>
        <article>
            <h3>Accessible by construction</h3>
            <p>The grid is one tab stop with a roving active cell exposed through
               <code>aria-activedescendant</code>, a polite live-region announcer for
               selection and clipboard changes, and no per-cell tabindex to trap anyone.</p>
        </article>
        <article>
            <h3>Extensible without forking</h3>
            <p>Custom column types, painters, editors, formatters and parse kinds register
               through PHP registries and their <code>window.LaraGrid</code> twins. The
               renderer never learns your types — it asks the registry.</p>
        </article>
    </div>

    <h2>The keyboard, which is the point</h2>
    <p class="lede">
        Two presets, one switch: <code>keymap('entry')</code> — the serpentine data-entry
        rhythm Tally and Busy trained a generation of operators on — or
        <code>keymap('excel')</code>, where Enter moves down and Tab moves right and nothing
        ever blocks.
    </p>

    <div class="table-wrap">
        <table class="doc">
            <thead><tr><th>Keys</th><th>Action</th></tr></thead>
            <tbody>
                <tr><td>Arrows · Tab · Home · End · PageUp / PageDown · Ctrl+edges</td><td>navigate</td></tr>
                <tr><td>Shift + movement · Ctrl+A</td><td>extend the selection / select all</td></tr>
                <tr><td>Ctrl+C</td><td>copy the selection as TSV — pastes into Excel and round-trips back</td></tr>
                <tr><td>Type · F2 · double-click</td><td>overwrite or edit a cell</td></tr>
                <tr><td>Space</td><td>toggle a checkbox or Yes/No cell in place</td></tr>
                <tr><td>Y / N</td><td>answer a Yes/No cell <em>and advance</em> — one keystroke per row</td></tr>
                <tr><td>Enter</td><td>commit and advance — serpentine in <code>entry</code>, down in <code>excel</code></td></tr>
                <tr><td>Delete</td><td>clear the selected cells</td></tr>
                <tr><td>Shift+Delete · F8</td><td>delete the row (guarded by <code>minRows</code>)</td></tr>
                <tr><td>F9 · Shift+F9</td><td>display grids: temporarily hide a row / restore all — footers recompute</td></tr>
                <tr><td>Insert · Ctrl+D</td><td>insert a row · fill down</td></tr>
                <tr><td>Ctrl+Z · Ctrl+Y / Ctrl+Shift+Z</td><td>undo · redo (editable grids)</td></tr>
                <tr><td>Ctrl+E</td><td>jump to the first error</td></tr>
                <tr><td>ContextMenu · Shift+F10</td><td>open the row's actions menu</td></tr>
                <tr><td>Escape</td><td>clear the selection / cancel the edit</td></tr>
            </tbody>
        </table>
    </div>

    <h2>Every demo on this site</h2>
    <div class="cards">
        @foreach (Seo::nav() as $link)
            @continue($link['href'] === '/')
            <article>
                <h3><a href="{{ $link['href'] }}">{{ $link['label'] }}</a></h3>
                <p>{{ $link['title'] }}</p>
            </article>
        @endforeach
    </div>

    <h2 id="faq">Frequently asked questions</h2>
    <div class="faq">
        @foreach (Seo::faqs() as $faq)
            <details @if($loop->first) open @endif>
                <summary><h3>{{ $faq['q'] }}</h3></summary>
                <p>{{ $faq['a'] }}</p>
            </details>
        @endforeach
    </div>

    <h2 id="source">The teaser grid's source</h2>
    <x-source-code title="Home source" :files="[
        'app/Http/Controllers/HomeController.php',
        'resources/views/home.blade.php',
    ]" />

    @php
        // Built here rather than inline: Blade's @json() directive cannot host a multi-line
        // array literal (its argument parser counts brackets on one line).
        $faqSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn (array $faq): array => [
                '@type' => 'Question',
                'name' => $faq['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
            ], Seo::faqs()),
        ];
    @endphp

    @push('schema')
        <script type="application/ld+json">@json($faqSchema)</script>
    @endpush

    @push('styles')
        <style>
            .hero { max-width: 78ch; margin: 0 0 2rem; }
            .hero h1 { font-size: 1.7rem; margin-bottom: .75rem; }
            .snippet { margin: 0 0 .75rem; padding: .7rem 1rem; border: 1px solid #e4e4e7; border-radius: .5rem; background: #f4f4f5; color: #18181b; overflow-x: auto; }
            html.dark .snippet { border-color: #27272a; background: #18181b; color: #e4e4e7; }
            .snippet code { background: none; border: 0; padding: 0; font-size: .85rem; color: inherit; }

            .table-wrap { overflow-x: auto; margin: 0 0 1.5rem; }
            table.doc { border-collapse: collapse; width: 100%; font-size: .85rem; background: #fff; }
            html.dark table.doc { background: #18181b; }
            table.doc th, table.doc td { border: 1px solid #e4e4e7; padding: .5rem .7rem; text-align: left; vertical-align: top; }
            html.dark table.doc th, html.dark table.doc td { border-color: #27272a; }
            table.doc th { background: #f4f4f5; font-weight: 600; }
            html.dark table.doc th { background: #101012; }

            .cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(17rem, 1fr)); gap: .9rem; margin: 0 0 1.5rem; }
            .cards article { border: 1px solid #e4e4e7; border-radius: .5rem; padding: .85rem 1rem; background: #fff; }
            html.dark .cards article { border-color: #27272a; background: #18181b; }
            .cards h3 { margin: 0 0 .35rem; font-size: .92rem; }
            .cards p { margin: 0; font-size: .84rem; color: #52525b; }
            html.dark .cards p { color: #a1a1aa; }

            .faq details { border: 1px solid #e4e4e7; border-radius: .5rem; background: #fff; margin: 0 0 .5rem; padding: .1rem .9rem; }
            html.dark .faq details { border-color: #27272a; background: #18181b; }
            .faq summary { cursor: pointer; padding: .6rem 0; }
            .faq summary h3 { display: inline; margin: 0; font-size: .92rem; }
            .faq p { margin: 0 0 .8rem; font-size: .86rem; color: #52525b; max-width: 80ch; }
            html.dark .faq p { color: #a1a1aa; }
        </style>
    @endpush
</x-layouts.app>
