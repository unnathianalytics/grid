<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Travelchords' }}</title>
    {{-- No @vite needed: Livewire and LaraGrid both auto-inject their own assets. --}}
    <style>
        body { font-family: system-ui, 'Segoe UI', Arial, sans-serif; margin: 0; background: #fafafa; color: #18181b; }
        main { max-width: 1200px; margin: 2rem auto; padding: 0 1.25rem; }
        h1 { font-size: 1.3rem; margin: 0 0 1rem; }
    </style>
</head>
<body>
    <main>
        {{ $slot }}
    </main>
</body>
</html>
