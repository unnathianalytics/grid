<x-layouts.app>
    <h1>LaraGrid Theming — Six Color Schemes, Dark Mode &amp; CSS Tokens</h1>

    <p class="lede">
        Every grid below is the <em>same definition</em> — identical columns, identical rows,
        identical footer. Only <code>-&gt;theme()</code> or <code>-&gt;density()</code> differs.
        Flip the <strong>◐ Theme</strong> switch in the header to see all of them in dark mode
        at once: dark is nothing but token flipping under a <code>.dark</code> ancestor, so
        every scheme ships a coordinated dark variant for free.
    </p>

    <p class="keys">
        Every visual is a <code>--lgrid-*</code> CSS custom property with a self-contained
        default, so the grid looks right on a page with no CSS framework at all — this demo has
        no Tailwind build. In a Tailwind v4 app it adopts your <code>--color-*</code>
        <code>@theme</code> palette automatically. All elements carry stable
        <code>lgrid-*</code> semantic classes, so any part is restylable and nothing is ever
        purged by a build tool. Print collapses to a clean black-on-white table — try
        <kbd>Ctrl</kbd>+<kbd>P</kbd>.
    </p>

    <h2>The six shipped schemes</h2>
    <p class="lede">
        <code>Grid::make('items')-&gt;theme('blue')</code> — an unknown name fails loudly at
        build time rather than rendering an unstyled grid. Set
        <code>'theme' =&gt; 'emerald'</code> in <code>config/laragrid.php</code> to change the
        app-wide default; any grid's own <code>-&gt;theme()</code> still wins.
    </p>

    @foreach ($themes as $name => $blurb)
        <section class="swatch">
            <h3><code>-&gt;theme('{{ $name }}')</code> <span class="muted">— {{ $blurb }}</span></h3>
            <x-laragrid :grid="$themeGrids[$name]" :rows="$rows" />
        </section>
    @endforeach

    <h2>A custom scheme in two properties</h2>
    <p class="lede">
        Internally each preset is just an accent pair; every surface derives from it through a
        shared <code>color-mix</code> formula. So your own brand scheme is a class with two
        custom properties, handed to <code>-&gt;themeClass()</code>:
    </p>

    <pre class="snippet"><code>/* your stylesheet */
.lgrid--theme-brand {
    --lgrid-theme-accent: #0f766e;
    --lgrid-theme-accent-dark: #2dd4bf;
}</code></pre>

    <pre class="snippet"><code>// your component
Grid::make('items')->themeClass('lgrid--theme-brand')</code></pre>

    <section class="swatch">
        <h3><code>-&gt;themeClass('lgrid--theme-brand')</code> <span class="muted">— teal, defined entirely in this page's CSS</span></h3>
        <x-laragrid :grid="$brandGrid" :rows="$rows" />
    </section>

    <h2>Row density</h2>
    <p class="lede">
        Three presets trade vertical room for rows-on-screen:
        <code>GridDensity::Compact</code> for registers an operator scans all day,
        <code>Normal</code> for entry screens, <code>Comfortable</code> for touch and
        presentation. The app-wide default lives at <code>laragrid.density</code>.
    </p>

    @foreach (['compact' => 'Compact', 'normal' => 'Normal', 'comfortable' => 'Comfortable'] as $key => $label)
        <section class="swatch">
            <h3><code>-&gt;density(GridDensity::{{ $label }})</code></h3>
            <x-laragrid :grid="$densityGrids[$key]" :rows="$rows" />
        </section>
    @endforeach

    <h2>Overriding individual tokens</h2>
    <p class="lede">
        A scheme sets the accent; every other token is still yours. Override them globally,
        under your own <code>-&gt;themeClass()</code>, or under <code>.dark</code>:
    </p>

    <pre class="snippet"><code>.lgrid {
    --lgrid-row-h: 1.75rem;        /* row height          */
    --lgrid-cell-pad-x: .5rem;     /* horizontal padding  */
    --lgrid-font-size: .8125rem;   /* cell type size      */
    --lgrid-border: #e4e4e7;       /* every grid line     */
    --lgrid-header-bg: #f4f4f5;    /* header surface      */
    --lgrid-footer-bg: #f4f4f5;    /* footer surface      */
    --lgrid-stripe-bg: #fafafa;    /* striped rows        */
    --lgrid-cell-bg: #fff;         /* cell surface        */
    --lgrid-text: #27272a;         /* cell text           */
    --lgrid-accent: #2563eb;       /* ring, selection     */
    --lgrid-error: #f43f5e;        /* invalid cells       */
    --lgrid-dirty: #fbbf24;        /* unsaved-cell corner */
}</code></pre>

    <h2 id="source">The source</h2>

    <x-source-code title="Theming source" :files="[
        'app/Http/Controllers/ThemesController.php',
        'resources/views/themes/index.blade.php',
    ]" />

    @push('styles')
        <style>
            /* The custom scheme demonstrated above — the whole of it. */
            .lgrid--theme-brand {
                --lgrid-theme-accent: #0f766e;
                --lgrid-theme-accent-dark: #2dd4bf;
            }

            .swatch { margin: 0 0 1.75rem; }
            .swatch h3 { margin: 0 0 .4rem; font-weight: 600; }
            .snippet { margin: 0 0 1rem; padding: .8rem 1rem; border: 1px solid #e4e4e7; border-radius: .5rem; background: #f4f4f5; color: #18181b; overflow-x: auto; }
            html.dark .snippet { border-color: #27272a; background: #18181b; color: #e4e4e7; }
            .snippet code { background: none; border: 0; padding: 0; font-size: .78rem; line-height: 1.6; color: inherit; }
        </style>
    @endpush
</x-layouts.app>
