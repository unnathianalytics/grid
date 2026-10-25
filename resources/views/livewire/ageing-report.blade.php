{{-- The whole nested component: one tag. The :rows are the display dataset; the download
     never trusts them — see exportRows() in App\Livewire\AgeingReport. --}}
<div>
    <x-laragrid :grid="$this->gridDefinition('ageing')" :rows="$rows" />
</div>
