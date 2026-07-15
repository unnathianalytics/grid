{{-- Read-only source viewer for the demo pages: highlights real project files and, given
     more than one, puts them behind a tab strip. Pass `panel` to add one further tab whose
     body is this component's slot — that is how Booking Entry shows its submit output
     beside the code that produces it.

     Which parts Livewire may morph is load-bearing here, because every LaraGrid cell op
     re-renders the whole page:
       · the tab bar and the file panels take wire:ignore — they are static, and skipping
         them is what keeps the selected tab alive (the server would otherwise reset
         aria-selected on every keystroke) and keeps 900-odd spans out of every diff;
       · the slot panel takes wire:ignore.self, which maps to morphdom's childrenOnly() —
         its own `hidden` stays client-owned while the caller's content still updates. --}}
@props([
    'files',            // list of project-relative paths, or label => path to caption a tab
    'title' => 'Source',
    'panel' => null,    // caption for a slot-bodied tab; null renders no such tab
])

@php
    $sources = collect($files)
        ->map(fn (string $path, int|string $key) => \App\Support\SourceHighlighter::file(
            $path,
            is_string($key) ? $key : null,
        ))
        ->values();

    $labels = $sources->pluck('label');

    if ($panel !== null) {
        $labels->push($panel);
    }

    // Derived from the props, never random: morphdom pairs old and new nodes by id, so an
    // id that changed each render would make Livewire replace these panels instead of
    // patching them — discarding both the selected tab and the wire:ignore flags with them.
    $id = 'src-'.substr(md5($title.'|'.$sources->pluck('path')->implode('|')), 0, 8);
    $tabbed = $labels->count() > 1;
    $slotIndex = $sources->count();
@endphp

<section {{ $attributes->class('src') }} data-src>
    <header class="src-bar" wire:ignore>
        @if ($tabbed)
            <div class="src-tabs" role="tablist" aria-label="{{ $title }}">
                @foreach ($labels as $i => $label)
                    <button type="button" class="src-tab" data-src-tab="{{ $i }}"
                            role="tab" id="{{ $id }}-tab-{{ $i }}"
                            aria-controls="{{ $id }}-panel-{{ $i }}"
                            aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                            tabindex="{{ $i === 0 ? '0' : '-1' }}">{{ $label }}</button>
                @endforeach
            </div>
        @else
            <span class="src-name">{{ $labels->first() }}</span>
        @endif

        <button type="button" class="src-copy" data-src-copy>Copy</button>
    </header>

    @foreach ($sources as $i => $source)
        <div class="src-panel" data-src-panel="{{ $i }}" @if ($i > 0) hidden @endif wire:ignore
             @if ($tabbed)
                 role="tabpanel" tabindex="0"
                 id="{{ $id }}-panel-{{ $i }}" aria-labelledby="{{ $id }}-tab-{{ $i }}"
             @endif>
            <div class="src-body">
                <pre class="src-lines" aria-hidden="true">{{ implode("\n", range(1, $source->lines)) }}</pre>
                <pre class="src-code"><code>{!! $source->html !!}</code></pre>
            </div>
            <footer class="src-path">{{ $source->path }}</footer>
        </div>
    @endforeach

    @if ($panel !== null)
        <div class="src-panel" data-src-panel="{{ $slotIndex }}" data-src-live hidden wire:ignore.self
             role="tabpanel" tabindex="0"
             id="{{ $id }}-panel-{{ $slotIndex }}" aria-labelledby="{{ $id }}-tab-{{ $slotIndex }}">
            <div class="src-slot">{{ $slot }}</div>
        </div>
    @endif
</section>

@once
    @push('styles')
        <style>
            .src { margin: 1.5rem 0 0; border: 1px solid #27272a; border-radius: .5rem; background: #18181b; overflow: hidden; }
            .src-bar { display: flex; align-items: stretch; background: #101012; border-bottom: 1px solid #27272a; }
            .src-tabs { display: flex; }
            .src-tab, .src-name { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: .78rem; }
            .src-tab { padding: .55rem .95rem; border: 0; border-bottom: 2px solid transparent; background: none; color: #a1a1aa; cursor: pointer; }
            .src-tab:hover { color: #e4e4e7; }
            .src-tab[aria-selected="true"] { background: #18181b; border-bottom-color: #e4e4e7; color: #fff; }
            .src-tab:focus-visible, .src-copy:focus-visible, .src-panel:focus-visible { outline: 2px solid #60a5fa; outline-offset: -2px; }
            .src-name { display: flex; align-items: center; padding: .55rem .95rem; color: #e4e4e7; }
            .src-copy { margin: 0 .5rem 0 auto; align-self: center; font: inherit; font-size: .7rem; padding: .25rem .6rem; border: 1px solid #3f3f46; border-radius: .3rem; background: none; color: #a1a1aa; cursor: pointer; }
            .src-copy:hover { border-color: #52525b; color: #fff; }
            .src-panel[hidden] { display: none; }
            .src-body { display: flex; max-height: 26rem; overflow: auto; }
            .src-lines, .src-code { margin: 0; padding: .9rem 0; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: .78rem; line-height: 1.55; }
            /* Sticky so the gutter holds its ground while the code scrolls sideways under it. */
            .src-lines { position: sticky; left: 0; z-index: 1; flex: none; padding-inline: .85rem .7rem; border-right: 1px solid #27272a; background: #18181b; color: #52525b; text-align: right; user-select: none; }
            .src-code { flex: 1; padding-inline: 1rem; color: #e4e4e7; white-space: pre; }
            .src-code code { font: inherit; }
            .src-path { padding: .4rem .95rem; border-top: 1px solid #27272a; background: #101012; color: #52525b; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: .68rem; }
            /* The slot panel earns the same body treatment as a file panel, so switching
               tabs does not change the shape of the card underneath you. */
            .src-slot { max-height: 26rem; padding: .9rem 1rem; overflow: auto; color: #e4e4e7; }
            .src-slot pre { margin: 0; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: .78rem; line-height: 1.55; white-space: pre; }
            .src-slot p { margin: 0; color: #71717a; font-size: .8rem; }

            .src-code .tok-comment { color: #8b8b96; font-style: italic; }
            .src-code .tok-kw      { color: #f472b6; }
            .src-code .tok-tag     { color: #f472b6; }
            .src-code .tok-blade   { color: #fb7185; }
            .src-code .tok-str     { color: #86efac; }
            .src-code .tok-num,
            .src-code .tok-const   { color: #fdba74; }
            .src-code .tok-var     { color: #7dd3fc; }
            .src-code .tok-class   { color: #fcd34d; }
            .src-code .tok-type    { color: #5eead4; }
            .src-code .tok-fn      { color: #93c5fd; }
            .src-code .tok-arg     { color: #d8b4fe; }
            .src-code .tok-attr    { color: #f0abfc; }
            .src-code .tok-prop,
            .src-code .tok-html    { color: #e4e4e7; }
            .src-code .tok-punct   { color: #a1a1aa; }
        </style>
    @endpush

    @push('scripts')
        <script>
            (() => {
                const rootOf = (el) => el.closest('[data-src]');

                const select = (tab) => {
                    const root = rootOf(tab);
                    const key = tab.dataset.srcTab;

                    root.querySelectorAll('[data-src-tab]').forEach((button) => {
                        const on = button === tab;
                        button.setAttribute('aria-selected', String(on));
                        button.tabIndex = on ? 0 : -1;
                    });

                    root.querySelectorAll('[data-src-panel]').forEach((panel) => {
                        panel.hidden = panel.dataset.srcPanel !== key;
                    });
                };

                const flash = (button, label) => {
                    button.textContent = label;
                    clearTimeout(button.resetTimer);
                    button.resetTimer = setTimeout(() => (button.textContent = 'Copy'), 1400);
                };

                const copy = async (button) => {
                    // A file panel copies its <code>; the slot panel copies its whole body.
                    // .src-slot is an ancestor of anything in it, so it wins document order.
                    const panel = rootOf(button).querySelector('[data-src-panel]:not([hidden])');
                    const code = panel.querySelector('code, .src-slot');

                    // navigator.clipboard is absent outside a secure context, which plain-http
                    // .test domains are — fall back to selecting the code so Ctrl+C still works.
                    if (!navigator.clipboard) {
                        const range = document.createRange();
                        range.selectNodeContents(code);
                        getSelection().removeAllRanges();
                        getSelection().addRange(range);
                        flash(button, 'Press Ctrl+C');
                        return;
                    }

                    try {
                        await navigator.clipboard.writeText(code.textContent);
                        flash(button, 'Copied');
                    } catch {
                        flash(button, 'Copy failed');
                    }
                };

                document.addEventListener('click', (event) => {
                    const tab = event.target.closest('[data-src-tab]');
                    if (tab) return select(tab);

                    const button = event.target.closest('[data-src-copy]');
                    if (button) copy(button);
                });

                // Roving tabindex: a tablist takes one Tab stop, arrows move within it.
                document.addEventListener('keydown', (event) => {
                    const tab = event.target.closest('[data-src-tab]');
                    if (!tab) return;

                    const tabs = [...rootOf(tab).querySelectorAll('[data-src-tab]')];
                    const step = { ArrowLeft: -1, ArrowRight: 1 }[event.key];
                    const next = step
                        ? tabs[(tabs.indexOf(tab) + step + tabs.length) % tabs.length]
                        : { Home: tabs[0], End: tabs[tabs.length - 1] }[event.key];

                    if (!next) return;

                    event.preventDefault();
                    select(next);
                    next.focus();
                });

                // The slot panel's body is server-rendered, so Livewire morphs new output
                // straight into it. Surface the tab when that output actually changes —
                // otherwise submitting would update a panel nobody is looking at. Comparing
                // text keeps the re-render that every cell edit triggers from firing this.
                document.querySelectorAll('[data-src-live]').forEach((panel) => {
                    let previous = panel.textContent.trim();

                    new MutationObserver(() => {
                        const current = panel.textContent.trim();
                        if (current === previous) return;

                        previous = current;
                        select(rootOf(panel).querySelector(`[data-src-tab="${panel.dataset.srcPanel}"]`));
                    }).observe(panel, { childList: true, subtree: true, characterData: true });
                });
            })();
        </script>
    @endpush
@endonce
