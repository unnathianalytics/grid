<div>
    <h1>Server-Side Laravel Datagrid — Sorting, Search, Filters, Export, Saved Views &amp; Sticky Filters</h1>

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
        <kbd>Enter</kbd> or double-click opens a resort
        (<code>rowActivate(…, navigate: true)</code>) ·
        click any header to sort (<kbd>Ctrl</kbd>-click column-selects instead) ·
        drag a column edge to resize, double-click it to autofit ·
        <kbd>F9</kbd> hides a row from the current view, <kbd>Shift</kbd>+<kbd>F9</kbd> brings them back ·
        <kbd>▦</kbd> chooses columns — <em>Email</em> starts hidden but is listed
        (<code>hiddenByDefault()</code>); <em>Phone</em> is <code>visible(false)</code> and never
        appears ·
        <kbd>⤓</kbd> exports CSV / XLSX / PDF, <kbd>❖</kbd> saves and recalls named views ·
        narrow the list, open a resort, come back: the search, filters and sort are still there
        and were applied <em>before</em> first paint (<code>persistQuery()</code>) — the page
        number deliberately is not ·
        search for something narrow and the pager disappears — <code>singlePageUpTo(200)</code>
        serves the whole filtered set whenever it fits.
    </p>

    <div class="host-controls">
        <span class="muted">Host seams:</span>
        <button type="button" wire:click="refresh" title="refreshGrid('resorts') — re-fetch the current view, cache-busted">↻ Refresh grid</button>
        <button type="button" wire:click="startClean" title="forgetGridQuery('resorts') — drop the sticky search / filters / sort, then reload">Start clean</button>
        <span class="muted">
            — <code>refreshGrid()</code> re-fetches the current view after a change the grid did
            not make; <code>forgetGridQuery()</code> drops the session-persisted query.
        </span>
    </div>

    <x-laragrid :grid="$this->gridDefinition('resorts')" />

    <h2>What this page demonstrates</h2>
    <ul class="lede">
        <li><strong>Whitelisted server-side pipeline</strong> — <code>query()</code>,
            <code>authorize()</code>, <code>searchable()</code>, <code>filters()</code>,
            <code>defaultSort()</code>, <code>paginate()</code>, <code>singlePageUpTo()</code>.</li>
        <li><strong>Two filter surfaces</strong> — toolbar controls (<code>SelectFilter</code>,
            <code>TernaryFilter</code>) and per-column header funnels via
            <code>filterable()</code>.</li>
        <li><strong>Three persistence lifetimes that compose</strong> —
            <code>persistQuery()</code> keeps the current search / filters / sort / per-page in the
            Laravel session (read on the server before page 1 is built, so no unfiltered flash;
            stale values drop themselves); <code>persistWidths()</code> keeps column widths and
            hidden columns in <code>localStorage</code>; <code>savedViews()</code> keeps named
            snapshots of all of it, per user, in the <code>laragrid_views</code> table.</li>
        <li><strong>Every readonly column type</strong> — serial, integer, text, select,
            checkbox, Yes/No, decimal, date, computed HTML badges and edit links, readonly and
            hidden carried values — plus the two kinds of "not shown":
            <code>hiddenByDefault()</code> (chooser can reveal) and <code>visible(false)</code>
            (never).</li>
        <li><strong>Row activation</strong> — <code>rowActivate()</code> resolves a per-row URL
            server-side (null = no affordance); <code>navigate: true</code> lets the grid follow
            it, otherwise <code>lgrid:activate</code> fires and the host decides.</li>
        <li><strong>Actions, fail-closed end to end</strong> — row <code>url()</code> and
            <code>call()</code> buttons with confirm / visible / authorize, bulk actions over
            checked rows, and toolbar actions.</li>
        <li><strong>Downloads of the current view</strong> — <code>exportable()</code> writes
            CSV, XLSX and PDF with zero extra dependencies, honouring the live sort, search and
            filters over the whole filtered set.</li>
        <li><strong>Host seams</strong> — <code>refreshGrid()</code> (the CRUD companion after a
            modal create/edit) and <code>forgetGridQuery()</code> (a tenant switch, a "start
            clean" control).</li>
        <li><strong>Layout</strong> — grouped two-tier headers, frozen columns, sticky header,
            stripes, compact density, a color theme, persisted widths, a live selection status
            bar and conditional row / cell classes.</li>
    </ul>

    <h2 id="source">The whole page, in one class</h2>
    <p class="lede">
        No Blade wiring, no JavaScript, no npm step: the view below is a heading, a hint box and
        the grid tag, and the grid definition is a single chained expression.
    </p>

    <x-source-code title="Resorts source" :files="[
        'app/Livewire/ResortsIndex.php',
        'resources/views/livewire/resorts-index.blade.php',
    ]" />

    @push('styles')
        <style>
            .host-controls { display: flex; flex-wrap: wrap; gap: .5rem .75rem; align-items: center; margin: 0 0 .6rem; font-size: .85rem; }
            .host-controls button { font: inherit; font-size: .8rem; padding: .3rem .7rem; border: 1px solid #d4d4d8; border-radius: .375rem; background: #fff; color: inherit; cursor: pointer; }
            .host-controls button:hover { border-color: #a1a1aa; }
            html.dark .host-controls button { background: #18181b; border-color: #3f3f46; }

            /* The classes ->rowClass() / ->cellClass() paint onto matching rows and cells. */
            .row-featured .lgrid-cell { font-weight: 600; }
            .cell-hot { color: #b45309; }
            html.dark .cell-hot { color: #fbbf24; }
        </style>
    @endpush
</div>
