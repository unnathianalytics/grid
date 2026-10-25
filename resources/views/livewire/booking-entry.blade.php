<div>
    <h1>Editable Livewire Datagrid — Inline Editing, Formula Columns, Focus Modes &amp; Async Pickers</h1>

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
        notice <kbd>Enter</kbd> never stops on <strong>Guests</strong> — a
        <code>FocusMode::Manual</code> column that defaults to 2; click it when a party is
        bigger — nor on <strong>Booked</strong>, a <code>FocusMode::Never</code> column stamped
        from a closure default ·
        tick <strong>Comp?</strong> and watch the rate zero itself and GST% grey out
        (<code>whenFilled</code> + <code>lockedWhen</code>, mirrored client-side, authoritative
        server-side) ·
        switch <strong>Tax</strong> to Exempt and the required cell moves from GST% to the
        exemption reason ·
        <kbd>Enter</kbd> on <strong>Note</strong> opens a host panel and resumes exactly where
        it left off ·
        <kbd>Y</kbd>/<kbd>N</kbd> answers Confirmed and advances in one keystroke ·
        <kbd>Delete</kbd> clears, <kbd>F9</kbd> or <kbd>Shift</kbd>+<kbd>Delete</kbd>
        deletes the row, <kbd>Insert</kbd> adds one, <kbd>F7</kbd> repeats the row above,
        <kbd>Ctrl</kbd>+<kbd>D</kbd> fills down,
        <kbd>Ctrl</kbd>+<kbd>Z</kbd>/<kbd>Ctrl</kbd>+<kbd>Y</kbd> undo and redo,
        <kbd>Ctrl</kbd>+<kbd>C</kbd> copies TSV and a multi-row paste from Excel maps straight
        onto the cells ·
        leave a required cell blank on a filled row and the footer's <strong>Review errors</strong>
        control lists it (<kbd>Ctrl</kbd>+<kbd>E</kbd> jumps there) ·
        reload the page mid-entry and a <strong>Restore / Discard</strong> bar brings the draft
        back from IndexedDB (<code>persistDraft()</code>) ·
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

        {{-- No wire:click: the script below runs grid.commitAndSave('save') instead, so Save can
             never race an open editor or a queued op, and mirrors the grid's canSave flag. --}}
        <button type="button" data-save class="btn-save">Save</button>
        <span class="muted">
            Save runs <code>grid.commitAndSave('save')</code> — commit the editor, flush, await a
            settled queue, then call the host — and is disabled while the grid reports
            <code>canSave: false</code>. The cleaned <code>gridRows()</code> land in the tab below.
        </span>
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
        <li><strong>Column focus modes</strong> — the ERP field behaviours as one chain:
            <code>FocusMode::Always</code> (the default stop-here field),
            <code>FocusMode::Manual</code> (skipped by <kbd>Enter</kbd>/<kbd>Tab</kbd>, editable on
            click — Guests) and <code>FocusMode::Never</code> (skipped and locked — Booked), each
            with a static or closure <code>default()</code> that seeds every fresh row.</li>
        <li><strong>Async picker with row enrichment</strong> —
            <code>SearchSelectColumn::optionsUsing()</code> streams options over an RPC and
            <code>onSelect()</code> pre-fills dependent cells in the same round trip.</li>
        <li><strong>Chained formula columns</strong> — <code>base → tax → amount</code>,
            evaluated live in the browser and authoritatively in PHP by twin evaluators.</li>
        <li><strong>Declarative cell rules</strong> — <code>rules()</code>,
            <code>required()</code> and <code>readonly()</code> (static or per-row closures),
            <code>requiredWhen()</code>, <code>lockedWhen()</code> and
            <code>whenFilled()</code> sibling mirrors.</li>
        <li><strong>Row lifecycle</strong> — per-column <code>default()</code>s as the new-row
            template, <code>autoAppend()</code>, <code>minRows()</code>, <code>padRows()</code>, and
            blank trailing rows that are invisible to validation, totals and <code>gridRows()</code>.</li>
        <li><strong>The completion circuit</strong> — an end-of-list picker exit fires
            <code>lgrid:complete</code>, <code>onCompleteFocus()</code> carries focus to Save,
            and <code>focusOutTo()</code> separately repairs the plain Tab exit.</li>
        <li><strong>The Save contract</strong> — <code>grid.commitAndSave('save')</code> instead
            of a racing <code>wire:click</code>, a Save button that mirrors the bubbling
            <code>lgrid:statechange</code> event's <code>canSave</code>, and the grid's own sync
            bar (Saved · Syncing · Offline · Retry) and <strong>Review errors</strong> list.</li>
        <li><strong>Draft recovery</strong> — <code>persistDraft()</code> keeps rows, queued ops,
            errors and focus in IndexedDB until the save is confirmed; a reload offers
            Restore / Discard, and every editable grid warns before a dirty navigation.</li>
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
            .btn-save:disabled { opacity: .45; cursor: default; }
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
            const root = $wire.$el.querySelector('[data-lgrid]');
            const save = $wire.$el.querySelector('[data-save]');

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

            // The Save contract, half one: lgrid:statechange bubbles from the grid root once
            // per settled state with {status, canSave, pending, modified, errors, …}. A
            // declarative host button simply mirrors canSave — never clickable while an op is
            // in flight, the grid is offline, or a validation error remains.
            root.addEventListener('lgrid:statechange', (event) => {
                if (event.detail.grid !== 'lines') return;
                save.disabled = !event.detail.canSave;
            });

            // Half two: commitAndSave() commits the open editor, flushes, awaits a stable
            // queue, then invokes $wire.save() and marks the draft saved. It refuses (throws)
            // while errors, offline state or a terminal sync failure remain — the grid's own
            // sync bar already says why, so the demo only logs the refusal.
            save.addEventListener('click', async () => {
                const grid = window.LaraGrid && window.LaraGrid.find(root);
                if (!grid) return;

                try {
                    await grid.commitAndSave('save');
                } catch (error) {
                    console.warn('[demo] save refused:', error.message);
                }
            });
        </script>
    @endscript
</div>
