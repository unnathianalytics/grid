{{-- The JavaScript half of this app's LaraGrid extensions — the twins of the PHP registrations
     in App\Providers\AppServiceProvider. Included once from the layout, so every page can use
     the 'inr' format, the 'rating' painter and the 'stars' cast.

     Registration goes through the ORDER-INDEPENDENT `pending` queue rather than a direct
     LaraGrid.registerX() call: with auto-injection the grid bundle is a DEFERRED script at the
     end of <head>, so this inline script runs first, when window.LaraGrid does not exist yet.
     The bundle merges onto whatever is already on window, drains the queue, and only then runs
     its first scan — so everything seeded here wins the FIRST PAINT rather than reconciling
     after it. After boot the queue is replaced by a live sink whose push() runs callbacks
     immediately, which is why this same idiom is correct from any script position. --}}
<script>
    (window.LaraGrid = window.LaraGrid || {}).pending = [
        (LG) => {
            // A custom PAINTER, for App\Grid\Columns\RatingColumn::painterId() === 'rating'.
            // Built with LG.el() — the same XSS-safe element factory the built-in renderers
            // use — so a custom cell never touches innerHTML.
            LG.registerPainter('rating', (cellEl, ctx) => {
                const stars = Math.max(0, Math.min(5, Math.round(Number(ctx.value) || 0)));
                cellEl.textContent = '';
                cellEl.appendChild(
                    LG.el('span', 'demo-rating', '★'.repeat(stars) + '☆'.repeat(5 - stars))
                );
                cellEl.setAttribute('aria-label', stars + ' of 5');
            });

            // The twin of App\Grid\Formatting\InrFormatter: same name, same output, both
            // runtimes. Indian digit grouping — the last three digits, then pairs.
            LG.registerFormatter('inr', (value, args) => {
                args = args || {};
                if (value === null || value === undefined || value === '') return '';

                const scale = Math.max(0, parseInt(args.scale || 0, 10) || 0);
                const symbol = args.symbol === undefined ? true : Boolean(args.symbol);

                const number = Number(value);
                if (!Number.isFinite(number)) return '';

                const parts = Math.abs(number).toFixed(scale).split('.');
                const whole = parts[0];
                const fraction = parts[1] || '';
                const grouped = whole.length > 3
                    ? whole.slice(0, -3).replace(/\B(?=(\d{2})+(?!\d))/g, ',') + ',' + whole.slice(-3)
                    : whole;

                return (number < 0 ? '-' : '') + (symbol ? '₹' : '') + grouped
                    + (fraction ? '.' + fraction : '');
            });

            // The twin of App\Grid\Casting\StarsCast — used when a RatingColumn sits on an
            // EDITABLE grid: `parse` produces the model value the client paints optimistically,
            // `editText` seeds the editor when it opens.
            LG.registerCast('stars', {
                parse: (text) => {
                    if (text === null || text === undefined || String(text).trim() === '') return null;
                    const n = Math.round(Number(text));
                    return Number.isFinite(n) ? Math.max(0, Math.min(5, n)) : null;
                },
                editText: (value) => (value === null || value === undefined ? '' : String(value)),
            });
        },
    ];
</script>
<style>
    .demo-rating { letter-spacing: .08em; color: #d97706; }
    html.dark .demo-rating { color: #fbbf24; }
</style>
