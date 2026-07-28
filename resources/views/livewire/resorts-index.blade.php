<div>
    <h1>Server-Side Laravel Datagrid — Sorting, Search, Filters, Export &amp; Saved Views</h1>

    <p class="lede">
        A readonly <strong>LaraGrid</strong> over 500 real rows, driven entirely from
        <a href="#source">one Livewire component class</a>. Every narrowing — the debounced global
        search, the toolbar filters, the header funnels, the sort caret, the page size — runs
        server-side through a whitelisted, fail-closed SQL pipeline, so nothing the browser sends
        can widen the query. Page 1 ships inside the initial payload for a zero-round-trip first
        paint; later pages stream over a renderless RPC with an LRU cache and idle prefetch.
    </p>

    <p class="keys">
        <strong>Try it:</strong>
        arrows / <kbd>Tab</kbd> / <kbd>Ctrl</kbd>+edges navigate ·
        <kbd>Shift</kbd>+movement or <kbd>Ctrl</kbd>+<kbd>A</kbd> selects ·
        <kbd>Ctrl</kbd>+<kbd>C</kbd> copies the selection as TSV straight into Excel ·
        <kbd>Enter</kbd> or double-click opens a resort ·
        click any header to sort (<kbd>Ctrl</kbd>-click column-selects instead) ·
        drag a column edge to resize, double-click it to autofit ·
        <kbd>▦</kbd> chooses columns, <kbd>⤓</kbd> exports CSV / XLSX / PDF,
        <kbd>❖</kbd> saves and recalls named views.
        Search for something narrow and the pager disappears — <code>singlePageUpTo(200)</code>
        serves the whole filtered set whenever it fits.
    </p>

    <x-laragrid :grid="$this->gridDefinition('resorts')" />

    <h2>What this page demonstrates</h2>
    <ul class="lede">
        <li><strong>Whitelisted server-side pipeline</strong> — <code>query()</code>,
            <code>authorize()</code>, <code>searchable()</code>, <code>filters()</code>,
            <code>defaultSort()</code>, <code>paginate()</code>, <code>singlePageUpTo()</code>.</li>
        <li><strong>Two filter surfaces</strong> — toolbar controls (<code>SelectFilter</code>,
            <code>TernaryFilter</code>) and per-column header funnels via
            <code>filterable()</code>.</li>
        <li><strong>Every readonly column type</strong> — serial, integer, text, select,
            checkbox, Yes/No, decimal, date, computed HTML badges and edit links, readonly and
            hidden carried values.</li>
        <li><strong>Actions, fail-closed end to end</strong> — row <code>url()</code> and
            <code>call()</code> buttons with confirm / visible / authorize, bulk actions over
            checked rows, and toolbar actions.</li>
        <li><strong>Downloads of the current view</strong> — <code>exportable()</code> writes
            CSV, XLSX and PDF with zero extra dependencies, honouring the live sort, search and
            filters over the whole filtered set.</li>
        <li><strong>Per-user saved views</strong> — <code>savedViews()</code> persists named
            snapshots of search + filters + sort + per-page + column layout, server-side and
            scoped to the authenticated operator.</li>
        <li><strong>Layout</strong> — grouped two-tier headers, frozen columns, sticky header,
            stripes, compact density, a color theme, persisted widths, a live selection status
            bar and conditional row / cell classes.</li>
    </ul>

    <h2 id="source">The whole page, in one class</h2>
    <p class="lede">
        No Blade wiring, no JavaScript, no npm step: the view below is four lines, and the grid
        definition is a single chained expression.
    </p>

    <x-source-code title="Resorts source" :files="[
        'app/Livewire/ResortsIndex.php',
        'resources/views/livewire/resorts-index.blade.php',
    ]" />

    @push('styles')
        <style>
            /* The classes ->rowClass() / ->cellClass() paint onto matching rows and cells. */
            .row-featured .lgrid-cell { font-weight: 600; }
            .cell-hot { color: #b45309; }
            html.dark .cell-hot { color: #fbbf24; }
        </style>
    @endpush
</div>
