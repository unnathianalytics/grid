<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'LaraGrid' }}</title>
    {{-- No @vite needed: Livewire and LaraGrid both auto-inject their own assets. --}}
    <style>
        body { font-family: system-ui, 'Segoe UI', Arial, sans-serif; margin: 0; background: #fafafa; color: #18181b; }
        main { max-width: 1200px; margin: 2rem auto; padding: 0 1.25rem; }
        h1 { font-size: 1.3rem; margin: 0 0 1rem; }
        nav.app-nav { background: #18181b; }
        nav.app-nav .inner { max-width: 1200px; margin: 0 auto; padding: 0 1.25rem; display: flex; gap: .25rem; align-items: center; }
        nav.app-nav .brand { color: #fff; font-weight: 600; margin-right: 1.25rem; padding: .85rem 0; }
        nav.app-nav a { color: #a1a1aa; text-decoration: none; padding: .85rem .9rem; font-size: .9rem; }
        nav.app-nav a:hover { color: #fff; }
        nav.app-nav a.active { color: #fff; box-shadow: inset 0 -2px 0 #fff; }
    </style>
    @stack('styles')
</head>
<body>
    <nav class="app-nav">
        <div class="inner">
            <span class="brand">LaraGrid</span>
            <a href="/resorts" @class(['active' => request()->is('resorts')])>Resorts</a>
            <a href="/booking" @class(['active' => request()->is('booking')])>Booking Entry</a>
            <a href="/resorts/create" @class(['active' => request()->is('resorts/create') || request()->is('resorts/*/edit')])>New Resort (LaraForm)</a>
        </div>
    </nav>
    <main>
        {{ $slot }}
    </main>
    @stack('scripts')
</body>
</html>
