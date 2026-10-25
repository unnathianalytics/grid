<x-layouts.app :wide="true">
    <h1>Display-Only Grid — Client-Side Sorting, What-If Totals, Custom Cells &amp; Trusted Exports</h1>

    <p class="lede">
        Both grids on this page are painted from <strong>in-memory rows</strong>: no
        <code>query()</code>, no <code>editable()</code> — just
        <code>&lt;x-laragrid :grid :rows&gt;</code>. This is the mode for computed report grids
        whose rows do not exist as table rows: trial balances, ageings, roll-ups. They still get
        the full keyboard model, the selection engine, the column chooser, resizable and
        persisted widths, and server-computed footer totals. The city roll-up sits on a plain
        Blade page with no Livewire component at all; the ageing grid below it is the same
        display mode hosted by a small nested Livewire component — purely so it can offer
        <em>trusted</em> downloads.
    </p>

    <p class="keys">
        <strong>Try it:</strong>
        click any header to sort — client-side, stable and type-aware, cycling
        asc → desc → the original order ·
        select a range and read Count / Sum / Average off the status bar ·
        press <kbd>F9</kbd> on a row to <strong>temporarily hide it</strong> and watch the
        footer totals recompute over what is left (the what-if view), then
        <kbd>Shift</kbd>+<kbd>F9</kbd> to bring everything back ·
        <kbd>Ctrl</kbd>+<kbd>C</kbd> copies the selection as TSV ·
        <kbd>▦</kbd> hides and restores columns — on the ageing grid the remaining columns
        re-fit the box (<code>fitColumns()</code>) ·
        <kbd>⤓</kbd> on the ageing grid downloads CSV / XLSX / PDF rebuilt on the server.
    </p>

    <h2>City roll-up <span class="muted">— a database aggregate, painted as rows, no Livewire</span></h2>

    <x-laragrid :grid="$cityGrid" :rows="$cityRows" />

    <p class="muted" style="margin-top:.6rem">
        The <strong>Tier</strong> column is an application-defined column type
        (<code>App\Grid\Columns\RatingColumn</code>) drawn by an application-registered painter,
        and the money columns use an application-registered <code>'inr'</code> format with
        Indian digit grouping. Neither the package nor the renderer knows either of them exists.
    </p>

    <h2>Receivables ageing <span class="muted">— hand-built rows, fixed height, fitted columns, trusted exports</span></h2>

    <p class="lede">
        Six literal rows in a PHP constant. <code>height('280px')</code> fixes the box,
        <code>fitColumns()</code> keeps every visible column inside it (declared widths become
        proportional weights, so there is never a horizontal scrollbar), and the footer still
        totals authoritatively. Press <kbd>F9</kbd> on the “Over 180 days” row to see the
        provision total drop.
    </p>

    <p class="lede">
        <strong>Why the Export control is here and not on the roll-up:</strong> LaraGrid never
        exports what a page painted — Blade <code>:rows</code>, a Livewire property or the DOM
        are not trusted sources. <code>exportRows()</code> is the separate, trusted half: a
        closure on a Livewire host that rebuilds the rows <em>on the server</em>, after
        <code>authorize()</code>, from only the normalized current view
        <code>{sort, dir, search, filters}</code>. Rendering stays in-memory; only the download
        gains a server source.
    </p>

    <livewire:ageing-report />

    <h2>What this page demonstrates</h2>
    <ul class="lede">
        <li><strong>Display mode</strong> — rows passed to the tag, painted as-is, with or
            without a Livewire component behind them.</li>
        <li><strong>Client-side sorting</strong> — <code>sortable()</code> without a DB target,
            plus <code>defaultSort()</code> applied at load.</li>
        <li><strong>What-if totals</strong> — <kbd>F9</kbd> / <kbd>Shift</kbd>+<kbd>F9</kbd> row
            hiding with live footer recomputation.</li>
        <li><strong>Trusted exports of an in-memory report</strong> —
            <code>exportRows()</code> + <code>exportable()</code> + <code>authorize()</code> on a
            nested Livewire component; the resolver honours the current sort so the file matches
            the screen, and a generator keeps the pipeline bounded-memory.</li>
        <li><strong>Fitted columns</strong> — <code>fitColumns()</code> suppresses the horizontal
            scrollbar and rebalances the visible columns on resize, hide and reveal.</li>
        <li><strong>The extension seams</strong> — a custom column type, a custom cell painter,
            a custom display format and a custom parse kind, each with a PHP half and a
            JavaScript twin registered under the same name.</li>
        <li><strong>Mode-appropriate refusals</strong> — <code>savedViews()</code>,
            <code>persistQuery()</code>, <code>call()</code> actions and bulk actions all fail
            loudly at build time on a display grid, because none of them can be honoured
            without a server-side row source; <code>exportable()</code> is refused unless
            <code>exportRows()</code> (or <code>query()</code>) supplies one.</li>
    </ul>

    <h2 id="source">The source</h2>

    <x-source-code title="Reports source" :files="[
        'app/Http/Controllers/ReportsController.php',
        'app/Livewire/AgeingReport.php',
        'resources/views/livewire/ageing-report.blade.php',
        'app/Grid/Columns/RatingColumn.php',
        'app/Grid/Formatting/InrFormatter.php',
        'app/Grid/Casting/StarsCast.php',
        'app/Providers/AppServiceProvider.php',
        'resources/views/partials/laragrid-extensions.blade.php',
        'resources/views/reports/index.blade.php',
    ]" />

    @push('styles')
        <style>
            .bar { display: inline-block; width: 60px; height: .5rem; margin-right: .4rem; border-radius: 9999px; background: color-mix(in oklab, currentColor 15%, transparent); overflow: hidden; vertical-align: middle; }
            .bar-fill { display: block; height: 100%; background: currentColor; opacity: .55; }
            .bar-num { font-variant-numeric: tabular-nums; }
        </style>
    @endpush
</x-layouts.app>
