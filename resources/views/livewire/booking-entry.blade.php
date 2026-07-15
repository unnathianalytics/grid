<div>
    <h1>Booking Entry <small style="font-weight:normal;color:#71717a">(editable LaraGrid demo — nothing is written to the database)</small></h1>

    <p style="color:#71717a;font-size:.85rem;margin:0 0 .75rem">
        Type to search a resort (rate auto-fills from its tariff) · Enter advances serpentine ·
        typing overwrites a cell · F2 edits in place · Delete clears · Shift+Delete / F7 removes the row ·
        Insert adds a row · Ctrl+D fills down · Ctrl+C copies TSV · Tab past the last cell lands on Save.
    </p>

    <x-laragrid :grid="$this->gridDefinition('lines')" :rows="$lines" />

    <div style="margin-top:1rem;display:flex;gap:.75rem;align-items:center">
        <button type="button" data-save wire:click="save"
                style="font:inherit;padding:.45rem 1.4rem;border:1px solid #18181b;border-radius:.375rem;background:#18181b;color:#fff;cursor:pointer">
            Save
        </button>
        <span style="color:#71717a;font-size:.85rem">Save captures the cleaned rows below and reseeds the grid.</span>
    </div>

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
</div>
