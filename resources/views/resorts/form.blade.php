<x-layouts.app>
    <x-slot:title>{{ $heading }} — LaraForm</x-slot:title>

    @push('styles')
        <style>
            .page-intro { color: #52525b; font-size: .9rem; margin: 0 0 1.25rem; max-width: 60ch; }
            .status-banner { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46;
                border-radius: .5rem; padding: .6rem .9rem; font-size: .875rem; margin-bottom: 1.25rem; }
            .status-banner a { color: #047857; font-weight: 600; }
            .edit-hint { font-size: .8125rem; color: #71717a; margin: -0.5rem 0 1.25rem; }

            .resort-form { background: #fff; border: 1px solid #e4e4e7; border-radius: .75rem;
                padding: 1.5rem; max-width: 56rem; margin-bottom: 2rem; }
            /* Two-column flow: LaraForm fields are plain blocks, so a CSS grid on the
               <form> is all the layout this page needs. */
            .resort-form form.lf-form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr));
                column-gap: 1.5rem; }
            .resort-form .span-2, .resort-form .lf-actions { grid-column: 1 / -1; }
            @media (max-width: 640px) {
                .resort-form form.lf-form { grid-template-columns: 1fr; }
            }
        </style>
    @endpush

    <h1>{{ $heading }}</h1>
    <p class="page-intro">
        Every LaraForm field type on one form. Put the mouse away: Enter advances
        (Shift+Enter back), the date takes <code>31/12</code> or <code>311224</code>,
        selects open on focus, the city combobox searches remotely, GST takes
        <code>y</code>/<code>n</code>, and Enter on Manager opens the contact panel.
        No Livewire, no page JS — assets auto-inject.
    </p>

    @if (session('status'))
        <div class="status-banner">
            {{ session('status') }} <a href="/resorts">View it in the grid →</a>
        </div>
    @endif

    @if ($latest !== null)
        <p class="edit-hint">
            Prefer an edit round-trip? <a href="{{ route('resorts.edit', $latest) }}">Edit “{{ $latest->name }}”</a>
            to see defaults, canonical values and old() repopulation.
        </p>
    @endif

    <div class="resort-form">
        {!! $form !!}
    </div>

    <x-source-code
        title="Source"
        :files="[
            'app/Http/Controllers/ResortFormController.php',
            'resources/views/resorts/form.blade.php',
        ]"
    />
</x-layouts.app>
