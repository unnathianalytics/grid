@php
    use App\Support\Seo;

    /** Resolved from the request path, so Livewire pages and controller views share one source. */
    $seo = Seo::resolve();
    $nav = Seo::nav();

    // This file is used two ways: Livewire renders it as a plain layout VIEW (#[Layout] passes
    // its params as view data), while controller pages render it as an anonymous COMPONENT
    // (<x-layouts.app :wide="true">), where extras arrive on $attributes. Normalise both.
    $wide = $wide ?? (isset($attributes) ? (bool) $attributes->get('wide', false) : false);
    $title = $title ?? (isset($attributes) ? $attributes->get('title') : null) ?? $seo->documentTitle();
@endphp
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    {{-- Primary --}}
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $seo->description }}">
    <meta name="keywords" content="{{ $seo->keywordList() }}">
    <meta name="author" content="{{ Seo::AUTHOR }}">
    <meta name="publisher" content="{{ Seo::AUTHOR }}">
    <meta name="application-name" content="{{ Seo::SITE }}">
    <meta name="subject" content="Laravel + Livewire datagrid package">
    <meta name="rating" content="general">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="theme-color" content="#18181b" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#09090b" media="(prefers-color-scheme: dark)">
    <meta name="color-scheme" content="light dark">
    <link rel="canonical" href="{{ $seo->canonical() }}">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="sitemap" type="application/xml" href="{{ url('/sitemap.xml') }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="{{ $seo->type }}">
    <meta property="og:site_name" content="{{ Seo::SITE }}">
    <meta property="og:locale" content="en_US">
    <meta property="og:title" content="{{ $seo->title }}">
    <meta property="og:description" content="{{ $seo->description }}">
    <meta property="og:url" content="{{ $seo->canonical() }}">
    <meta property="og:image" content="{{ url($seo->image) }}">
    <meta property="og:image:alt" content="{{ Seo::SITE }} — {{ Seo::TAGLINE }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    {{-- Twitter / X card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo->title }}">
    <meta name="twitter:description" content="{{ $seo->description }}">
    <meta name="twitter:image" content="{{ url($seo->image) }}">
    <meta name="twitter:image:alt" content="{{ Seo::SITE }} — {{ Seo::TAGLINE }}">

    {{-- Structured data: the software itself, the site, this page, and its breadcrumb trail.
         Pages may push a further block (the home page adds FAQPage) onto the `schema` stack. --}}
    <script type="application/ld+json">@json($seo->jsonLd())</script>
    @stack('schema')

    {{-- No @vite needed: Livewire and LaraGrid both auto-inject their own assets. --}}
    <style>
        :root { color-scheme: light; }
        html.dark { color-scheme: dark; }
        * { box-sizing: border-box; }
        body { font-family: system-ui, 'Segoe UI', Arial, sans-serif; margin: 0; background: #fafafa; color: #18181b; line-height: 1.55; }
        html.dark body { background: #09090b; color: #e4e4e7; }
        main { max-width: 1200px; margin: 2rem auto 3rem; padding: 0 1.25rem; }
        main.wide { max-width: 1360px; }
        h1 { font-size: 1.35rem; margin: 0 0 .35rem; line-height: 1.25; }
        h2 { font-size: 1.05rem; margin: 2rem 0 .5rem; }
        h3 { font-size: .92rem; margin: 1.25rem 0 .4rem; }
        p { margin: 0 0 .75rem; }
        a { color: #2563eb; }
        html.dark a { color: #93c5fd; }
        code { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: .85em; background: #f4f4f5; border: 1px solid #e4e4e7; border-radius: .25rem; padding: .05em .35em; }
        html.dark code { background: #27272a; border-color: #3f3f46; }
        .lede { color: #52525b; font-size: .92rem; max-width: 78ch; margin: 0 0 1rem; }
        html.dark .lede { color: #a1a1aa; }
        .muted { color: #71717a; font-size: .85rem; }
        .keys { color: #52525b; font-size: .82rem; background: #f4f4f5; border: 1px solid #e4e4e7; border-radius: .375rem; padding: .5rem .75rem; margin: 0 0 .9rem; max-width: 100%; }
        html.dark .keys { background: #18181b; border-color: #27272a; color: #a1a1aa; }
        .keys kbd { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: .95em; background: #fff; border: 1px solid #d4d4d8; border-bottom-width: 2px; border-radius: .25rem; padding: 0 .3em; }
        html.dark .keys kbd { background: #27272a; border-color: #52525b; }

        nav.app-nav { background: #18181b; position: sticky; top: 0; z-index: 20; }
        nav.app-nav .inner { max-width: 1360px; margin: 0 auto; padding: 0 1.25rem; display: flex; gap: .1rem; align-items: center; flex-wrap: wrap; }
        nav.app-nav .brand { color: #fff; font-weight: 700; margin-right: 1rem; padding: .85rem 0; text-decoration: none; letter-spacing: -.01em; }
        nav.app-nav a { color: #a1a1aa; text-decoration: none; padding: .85rem .7rem; font-size: .85rem; white-space: nowrap; }
        nav.app-nav a:hover { color: #fff; }
        nav.app-nav a.active { color: #fff; box-shadow: inset 0 -2px 0 #fff; }
        nav.app-nav .spacer { flex: 1 1 auto; }
        nav.app-nav button.mode { margin-left: .5rem; font: inherit; font-size: .8rem; background: none; border: 1px solid #3f3f46; border-radius: .375rem; color: #a1a1aa; padding: .2rem .55rem; cursor: pointer; }
        nav.app-nav button.mode:hover { color: #fff; border-color: #52525b; }

        footer.app-foot { border-top: 1px solid #e4e4e7; background: #f4f4f5; margin-top: 3rem; }
        html.dark footer.app-foot { border-color: #27272a; background: #101012; }
        footer.app-foot .inner { max-width: 1200px; margin: 0 auto; padding: 1.5rem 1.25rem 2rem; font-size: .82rem; color: #52525b; }
        html.dark footer.app-foot .inner { color: #a1a1aa; }
        footer.app-foot ul { list-style: none; display: flex; flex-wrap: wrap; gap: .25rem 1rem; margin: 0 0 .75rem; padding: 0; }
    </style>
    @stack('styles')

    {{-- This app's LaraGrid extensions (custom painter / format / cast), seeded before the
         package bundle's deferred script executes so they win the first paint. --}}
    @include('partials.laragrid-extensions')

    <script>
        // Applied before first paint so a dark-mode reload never flashes white. The grid's
        // own dark variant keys off this same `.dark` class — token flipping, nothing more.
        try {
            const stored = localStorage.getItem('lgrid-demo-mode');
            const dark = stored ? stored === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        } catch (e) {}
    </script>
</head>
<body>
    <a href="#content" style="position:absolute;left:-9999px" onfocus="this.style.left='.5rem'" onblur="this.style.left='-9999px'">Skip to content</a>

    <nav class="app-nav" aria-label="Primary">
        <div class="inner">
            <a href="/" class="brand" title="{{ Seo::SITE }} — {{ Seo::TAGLINE }}">LaraGrid</a>
            @foreach ($nav as $link)
                <a href="{{ $link['href'] }}" title="{{ $link['title'] }}" @class(['active' => $link['active']])
                   @if ($link['active']) aria-current="page" @endif>{{ $link['label'] }}</a>
            @endforeach
            <span class="spacer"></span>
            <button type="button" class="mode" data-mode-toggle aria-label="Toggle dark mode">◐ Theme</button>
        </div>
    </nav>

    <main id="content" @class(['wide' => $wide ?? false])>
        {{ $slot }}
    </main>

    <footer class="app-foot">
        <div class="inner">
            <ul>
                @foreach ($nav as $link)
                    <li><a href="{{ $link['href'] }}" title="{{ $link['title'] }}">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
            <p style="margin:0">
                <strong>{{ Seo::SITE }}</strong> — {{ Seo::TAGLINE }}.
                Install with <code>composer require {{ Seo::PACKAGE }}</code> ·
                <a href="{{ Seo::PACKAGIST }}" rel="noopener nofollow">Packagist</a> ·
                <a href="{{ Seo::REPO }}" rel="noopener nofollow">GitHub</a> ·
                MIT licensed by {{ Seo::AUTHOR }}.
            </p>
        </div>
    </footer>

    <script>
        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-mode-toggle]')) return;
            const dark = document.documentElement.classList.toggle('dark');
            try { localStorage.setItem('lgrid-demo-mode', dark ? 'dark' : 'light'); } catch (e) {}
        });
    </script>
    @stack('scripts')
</body>
</html>
