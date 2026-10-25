<div>
    <h1>Double-Entry Voucher Grid — Balanced Completion, Dr/Cr Locking &amp; Autofill</h1>

    <p class="lede">
        The accounting shape LaraGrid was extracted from. Entry ends by <em>balancing</em>:
        while Σ&nbsp;Debit ≠ Σ&nbsp;Credit the grid keeps auto-appending rows, and the instant
        the two sides agree, <kbd>Enter</kbd> past the last cell stops growing the grid and
        fires the completion signal instead — which carries focus to a Post button that was
        disabled until that very commit.
    </p>

    <p class="keys">
        <strong>Try it:</strong>
        type a few letters of an account (or its code) in <strong>Particulars</strong> ·
        <kbd>Enter</kbd> skips straight over <strong>Group</strong> — it is a
        <code>FocusMode::Manual</code> column — but click it and pick <em>Expenses</em>, and the
        Particulars list on that row narrows to the 5xxx accounts
        (<code>optionsUsing(fn ($term, $row))</code> + <code>optionsDependOn(['group'])</code>);
        picking a ledger fills its group back in ·
        type an amount in <strong>Debit</strong> and the Dr/Cr selector flips to Dr while Credit
        blanks itself — and vice versa (<code>whenFilled</code> mirrors it instantly,
        <code>afterCellChange</code> makes it authoritative) ·
        the greyed cell on each row is a <code>lockedWhen()</code> mask: the editor refuses it
        and serpentine <kbd>Enter</kbd> skips straight over it ·
        leave the voucher out of balance, land on the empty amount cell of the deficit side, and
        the balancing figure is <strong>pre-filled</strong> — accept it with <kbd>Enter</kbd> or
        overtype it ·
        once it balances, one more <kbd>Enter</kbd> lands on Post, and <kbd>Enter</kbd> again
        posts the voucher.
    </p>

    <div class="voucher-head">
        <label for="voucher-date">Date</label>
        <input id="voucher-date" type="date" wire:model.blur="voucherDate">

        <span class="balance @if($this->isBalanced) ok @endif">
            @if ($this->isBalanced)
                ✓ Balanced
            @else
                Difference <strong>{{ number_format(abs($this->difference), 2) }}</strong>
                {{ $this->difference > 0 ? 'Cr short' : 'Dr short' }}
            @endif
        </span>
    </div>

    <x-laragrid :grid="$this->gridDefinition('voucher')" :rows="$entries" />

    <div class="entry-foot">
        <label for="narration" class="muted">Narration (Tab off the last cell lands here)</label>
        <input id="narration" type="text" wire:model.blur="narration" placeholder="Being…">

        {{-- Disabled server-side until the voucher balances (re-rendered by refreshesHost);
             the click runs grid.commitAndSave('post') — see the script below. --}}
        <button type="button" data-post class="btn-save"
                @disabled(! $this->isBalanced)>Post Voucher</button>
        <span class="muted">Enabled only while the voucher balances; posts through <code>commitAndSave()</code>.</span>
    </div>

    <h2>What this page demonstrates</h2>
    <ul class="lede">
        <li><strong><code>completeWhenBalanced('dr', 'cr')</code></strong> — the balancing guard,
            plus its autofill of the deficit side through the normal commit pipeline.</li>
        <li><strong>A <code>FocusMode::Manual</code> column</strong> — the ERP "semi-variable"
            field: serpentine navigation never stops on Group, a click still edits it.</li>
        <li><strong>Row-dependent picker options</strong> — <code>optionsUsing()</code> receives
            the row and filters by its Group; <code>optionsDependOn(['group'])</code> keys the
            client's per-term option cache by it, so one row's list is never reused for a row on
            a different group, while the server still re-runs the closure authoritatively.</li>
        <li><strong>A mutually exclusive column pair</strong> — <code>whenFilled()</code> client
            mirrors, <code>lockedWhen()</code> navigation masks, and an
            <code>afterCellChange()</code> hook that makes "typed side wins" authoritative.</li>
        <li><strong><code>SyncPolicy::PerRow</code></strong> — ops batch until the cursor leaves
            the row instead of flushing per cell.</li>
        <li><strong>An async picker with no database</strong> — <code>optionsUsing()</code> over
            a plain PHP array, with the account code painted as muted option meta.</li>
        <li><strong><code>newRowUsing()</code></strong> as the grid-level row template, next to
            the booking grid's per-column <code>default()</code>s — both seed the same blank-row
            template.</li>
        <li><strong>The retrying focus target</strong> — <code>onCompleteFocus()</code> lands on
            a Post button that only enables on the commit that completed the voucher, and the
            button posts through <code>grid.commitAndSave('post')</code>.</li>
    </ul>

    <h2 id="source">The whole voucher, in one class</h2>

    <x-source-code title="Journal Entry source" panel="Posted payload" :files="[
        'app/Livewire/JournalEntry.php',
        'resources/views/livewire/journal-entry.blade.php',
    ]">
        @if ($posted !== [])
            <pre>{{ json_encode($posted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        @else
            <p>Balance a voucher and hit Post — the cleaned gridRows() output lands here.</p>
        @endif
    </x-source-code>

    @push('styles')
        <style>
            .voucher-head { display: flex; flex-wrap: wrap; gap: .5rem .9rem; align-items: center; margin: 0 0 .6rem; font-size: .85rem; }
            .voucher-head input { font: inherit; font-size: .85rem; padding: .3rem .5rem; border: 1px solid #d4d4d8; border-radius: .375rem; background: #fff; color: inherit; }
            html.dark .voucher-head input { background: #18181b; border-color: #3f3f46; }
            .balance { margin-left: auto; padding: .25rem .75rem; border-radius: 9999px; background: #fef3c7; color: #92400e; font-weight: 600; }
            .balance.ok { background: #d1fae5; color: #065f46; }
            html.dark .balance { background: #422006; color: #fbbf24; }
            html.dark .balance.ok { background: #052e16; color: #34d399; }

            .entry-foot { display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; margin-top: 1rem; }
            .entry-foot input#narration { font: inherit; font-size: .85rem; padding: .4rem .6rem; border: 1px solid #d4d4d8; border-radius: .375rem; background: #fff; color: inherit; min-width: 18rem; }
            html.dark .entry-foot input#narration { background: #18181b; border-color: #3f3f46; }
            .btn-save { font: inherit; padding: .45rem 1.4rem; border: 1px solid #18181b; border-radius: .375rem; background: #18181b; color: #fff; cursor: pointer; }
            .btn-save:disabled { opacity: .45; cursor: default; }
            html.dark .btn-save { background: #e4e4e7; border-color: #e4e4e7; color: #18181b; }

            /* ->rowClass() targets: a faint side stripe per Dr / Cr row. */
            .row-dr .lgrid-cell:first-child { box-shadow: inset 3px 0 0 #7c3aed; }
            .row-cr .lgrid-cell:first-child { box-shadow: inset 3px 0 0 #0ea5e9; }
        </style>
    @endpush

    @script
        <script>
            // Post goes through the grid, not around it: commitAndSave() commits any open
            // editor, flushes the PerRow batch, waits for every op to be acknowledged, and only
            // then invokes $wire.post(). It refuses while a validation error remains.
            $wire.$el.querySelector('[data-post]').addEventListener('click', async () => {
                const grid = window.LaraGrid && window.LaraGrid.find($wire.$el.querySelector('[data-lgrid]'));
                if (!grid) return;

                try {
                    await grid.commitAndSave('post');
                } catch (error) {
                    console.warn('[demo] post refused:', error.message);
                }
            });
        </script>
    @endscript
</div>
