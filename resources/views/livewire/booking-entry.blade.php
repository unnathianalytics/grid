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

    @if ($saved !== [])
        <h2 style="font-size:1rem;margin-top:1.5rem">Saved payload (gridRows() output)</h2>
        <pre style="background:#18181b;color:#e4e4e7;padding:1rem;border-radius:.5rem;font-size:.8rem;overflow:auto">{{ json_encode($saved, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    @endif
</div>
