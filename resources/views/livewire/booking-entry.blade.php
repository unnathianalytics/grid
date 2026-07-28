<div>
    <h1>Editable Livewire Datagrid — Inline Editing, Formula Columns &amp; Async Pickers</h1>

    <p class="lede">
        A spreadsheet-grade entry screen driven entirely from
        <a href="#source">one Livewire component class</a>: the client applies every keystroke
        optimistically and streams typed ops to the server, where each write is authorized,
        cast, validated, run through your hooks and recomputed for formula columns — then the
        response reconciles the authoritative values back into the grid. Rows are addressed by
        stable keys, never positions. <em>Nothing here is written to the database.</em>
    </p>

    <p class="keys">
        <strong>Try it:</strong>
        type a resort name — the rate and city auto-fill from the pick, and the option list
        shows the tariff as muted meta ·
        pick two dates and <strong>Nights</strong> derives itself, then Base, GST and Amount
        recompute in the same round trip ·
        tick <strong>Comp?</strong> and watch the rate zero itself and GST% grey out
        (<code>whenFilled</code> + <code>lockedWhen</code>, mirrored client-side, authoritative
        server-side) ·
        switch <strong>Tax</strong> to Exempt and the required cell moves from GST% to the
        exemption reason ·
        <kbd>Enter</kbd> on <strong>Note</strong> opens a host panel and resumes exactly where
        it left off ·
        <kbd>Y</kbd>/<kbd>N</kbd> answers Confirmed and advances in one keystroke ·
        <kbd>F2</kbd> edits in place, <kbd>Delete</kbd> clears, <kbd>Shift</kbd>+<kbd>Delete</kbd>
        deletes the row, <kbd>Insert</kbd> adds one, <kbd>Ctrl</kbd>+<kbd>D</kbd> fills down,
        <kbd>Ctrl</kbd>+<kbd>Z</kbd>/<kbd>Ctrl</kbd>+<kbd>Y</kbd> undo and redo,
        <kbd>Ctrl</kbd>+<kbd>C</kbd> copies TSV and a multi-row paste from Excel maps straight
        onto the cells ·
        pick <em>&lt;-- End of List --&gt;</em> on a blank row to finish — focus lands on Save.
    </p>

    <div class="running">
        <span><strong>{{ $lineCount }}</strong> line{{ $lineCount === 1 ? '' : 's' }} entered</span>
        <span>Running total <strong>{{ number_format($runningTotal, 2) }}</strong></span>
        <span class="muted">— this badge is host chrome, kept live by <code>refreshesHost()</code>.</span>
    </div>

    <x-laragrid :grid="$this->gridDefinition('lines')" :rows="$lines" />

    <div class="entry-foot">
        <label for="remarks" class="muted">Remarks (Tab off the last cell lands here)</label>
        <input id="remarks" type="text" wire:model.blur="remarks" placeholder="Voucher remarks…">

        <button type="button" data-save wire:click="save" class="btn-save">Save</button>
        <span class="muted">Save captures the cleaned <code>gridRows()</code> output and reseeds the grid.</span>
    </div>

    {{-- The ->opensPanel('line-notes') host modal. The grid keeps its active cell while this is
         open and resumes the stashed advance the moment closePanel() fires gridPanelDone(). --}}
    @if ($panelRow !== null)
        <div class="panel-backdrop" wire:click.self="closePanel(false)">
            <div class="panel" role="dialog" aria-modal="true" aria-labelledby="panel-title"
                 wire:keydown.escape="closePanel(false)">
                <h2 id="panel-title">Line notes</h2>
                <p class="muted">
                    Data that belongs to the line but has no column of its own — the classic
                    reason a cell hands off to a host panel.
                </p>

                <label for="panel-requests">Special requests</label>
                <textarea id="panel-requests" rows="3" wire:model="panelRequests" autofocus></textarea>

                <label for="panel-arrival">Expected arrival time</label>
                <input id="panel-arrival" type="time" wire:model="panelArrival">

                <div class="panel-actions">
                    <button type="button" wire:click="closePanel(false)">Cancel</button>
                    <button type="button" class="btn-save" wire:click="closePanel(true)">Keep</button>
                </div>
            </div>
        </div>
    @endif

    <h2>What this page demonstrates</h2>
    <ul class="lede">
        <li><strong>Optimistic client, authoritative server</strong> — a typed op protocol,
            validation on both sides, and formula recomputation server-side after your hooks.</li>
        <li><strong>Async picker with row enrichment</strong> —
            <code>SearchSelectColumn::optionsUsing()</code> streams options over an RPC and
            <code>onSelect()</code> pre-fills dependent cells in the same round trip.</li>
        <li><strong>Chained formula columns</strong> — <code>base → tax → amount</code>,
            evaluated live in the browser and authoritatively in PHP by twin evaluators.</li>
        <li><strong>Declarative cell rules</strong> — <code>rules()</code>,
            <code>required()</code> and <code>readonly()</code> (static or per-row closures),
            <code>requiredWhen()</code>, <code>lockedWhen()</code> and
            <code>whenFilled()</code> sibling mirrors.</li>
        <li><strong>Row lifecycle</strong> — <code>newRowUsing()</code> templates,
            <code>autoAppend()</code>, <code>minRows()</code>, <code>padRows()</code>, and blank
            trailing rows that are invisible to validation, totals and <code>gridRows()</code>.</li>
        <li><strong>The completion circuit</strong> — an end-of-list picker exit fires
            <code>lgrid:complete</code>, <code>onCompleteFocus()</code> carries focus to Save,
            and <code>focusOutTo()</code> separately repairs the plain Tab exit.</li>
        <li><strong>Host hand-offs</strong> — <code>opensPanel()</code> with
            <code>lgrid:panel</code> / <code>gridPanelDone()</code>, and
            <code>refreshesHost()</code> for live chrome outside the grid.</li>
        <li><strong>Undo that never bypasses the server</strong> — 100 steps, one gesture per
            step; each undone step replays through the same op protocol as typing.</li>
    </ul>

    <h2 id="source">The whole screen, in one class</h2>

    <x-source-code title="Booking Entry source" panel="Saved payload" :files="[
        'app/Livewire/BookingEntry.php',
        'resources/views/livewire/booking-entry.blade.php',
    ]">
        @if ($saved !== [])
            <pre>{{ json_encode($saved, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        @else
            <p>Fill a line and hit Save — the cleaned gridRows() output lands here.</p>
        @endif
    </x-source-code>

    @push('styles')
        <style>
            .running { display: flex; flex-wrap: wrap; gap: .25rem 1.25rem; align-items: baseline; margin: 0 0 .6rem; padding: .45rem .75rem; border: 1px solid #e4e4e7; border-radius: .375rem; background: #fff; font-size: .85rem; }
            html.dark .running { background: #18181b; border-color: #27272a; }
            .entry-foot { display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; margin-top: 1rem; }
            .entry-foot input#remarks { font: inherit; font-size: .85rem; padding: .4rem .6rem; border: 1px solid #d4d4d8; border-radius: .375rem; background: #fff; color: inherit; min-width: 16rem; }
            html.dark .entry-foot input#remarks { background: #18181b; border-color: #3f3f46; }
            .btn-save { font: inherit; padding: .45rem 1.4rem; border: 1px solid #18181b; border-radius: .375rem; background: #18181b; color: #fff; cursor: pointer; }
            html.dark .btn-save { background: #e4e4e7; border-color: #e4e4e7; color: #18181b; }

            /* ->rowClass() / ->cellClass() targets. */
            .row-comp .lgrid-cell { font-style: italic; }
            .cell-big { color: #b45309; font-weight: 700; }
            html.dark .cell-big { color: #fbbf24; }

            .panel-backdrop { position: fixed; inset: 0; z-index: 50; display: grid; place-items: center; background: rgba(9, 9, 11, .55); padding: 1rem; }
            .panel { width: min(28rem, 100%); background: #fff; color: #18181b; border-radius: .5rem; padding: 1rem 1.25rem 1.25rem; box-shadow: 0 20px 50px -20px rgba(0,0,0,.6); }
            html.dark .panel { background: #18181b; color: #e4e4e7; }
            .panel h2 { margin: 0 0 .25rem; font-size: 1rem; }
            .panel label { display: block; margin: .75rem 0 .25rem; font-size: .8rem; font-weight: 600; }
            .panel textarea, .panel input { width: 100%; font: inherit; font-size: .85rem; padding: .4rem .55rem; border: 1px solid #d4d4d8; border-radius: .375rem; background: transparent; color: inherit; }
            html.dark .panel textarea, html.dark .panel input { border-color: #3f3f46; }
            .panel-actions { display: flex; justify-content: flex-end; gap: .5rem; margin-top: 1rem; }
            .panel-actions button { font: inherit; font-size: .85rem; padding: .35rem .9rem; border: 1px solid #d4d4d8; border-radius: .375rem; background: transparent; color: inherit; cursor: pointer; }
            html.dark .panel-actions button { border-color: #3f3f46; }
        </style>
    @endpush

    @script
        <script>
            // The grid hands its Enter off to us: open the host panel for the row it names.
            // Every exit path calls closePanel(), which fires gridPanelDone() and lets the
            // stashed advance run — so the cursor never gets stranded.
            document.addEventListener('lgrid:panel', (event) => {
                if (event.detail.grid !== 'lines' || event.detail.panel !== 'line-notes') return;
                $wire.openPanel(event.detail.rowKey);
            });

            // The completion signal, for anything beyond the packaged focus move.
            document.addEventListener('lgrid:complete', (event) => {
                if (event.detail.grid === 'lines') console.debug('[demo] entry complete');
            });
        </script>
    @endscript
</div>
