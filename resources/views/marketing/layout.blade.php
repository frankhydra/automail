<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @auth data-palette="{{ \App\Support\Theme::current(Auth::user()) }}" @endauth>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        $siteName = config('app.name', 'AutoMail');
        $pageTitle = trim($__env->yieldContent('title')) ?: $siteName.': '.config('marketing.tagline');
        $pageDescription = trim($__env->yieldContent('description')) ?: 'Grow your audience, design emails, automate journeys and see what works, with an AI co-writer built in.';
    @endphp

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800" rel="stylesheet">

    <!-- Dark mode and colour theme are applied before the page paints, so there is no flash. -->
    <script>
        (function () {
            document.documentElement.classList.add('js');
            var stored = localStorage.getItem('automail-theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (!stored && prefersDark)) { document.documentElement.classList.add('dark'); }
        })();
    </script>
    @include('layouts._palette')

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Gentle fade-up as sections scroll into view. Content is fully visible without JavaScript. */
        .js [data-reveal] { opacity: 0; transform: translateY(14px); transition: opacity .6s ease, transform .6s ease; }
        .js [data-reveal].is-in { opacity: 1; transform: none; }
        @media (prefers-reduced-motion: reduce) { .js [data-reveal] { opacity: 1; transform: none; transition: none; } html { scroll-behavior: auto !important; } }
        html { scroll-behavior: smooth; }
        details > summary { list-style: none; }
        details > summary::-webkit-details-marker { display: none; }
        details[open] .faq-chevron { transform: rotate(180deg); }
        .dotgrid { background-image: radial-gradient(rgb(var(--sidebar) / .13) 1px, transparent 1px); background-size: 22px 22px; }
        .dark .dotgrid { background-image: radial-gradient(rgb(255 255 255 / .07) 1px, transparent 1px); }
    </style>
</head>
<body class="font-sans antialiased bg-paper text-ink selection:bg-accent selection:text-white">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:px-4 focus:py-2 focus:rounded-lg focus:bg-accent focus:text-white">Skip to content</a>

    @php
        $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-paper';
        $caps = config('marketing.capabilities');
        $auds = config('marketing.audiences');
    @endphp

    <!-- ============================== Header ============================== -->
    <header x-data="{ mega: false, menu: false, dark: document.documentElement.classList.contains('dark') }"
            @keydown.escape.window="mega = false; menu = false"
            class="sticky top-0 z-50 border-b border-border backdrop-blur-lg"
            style="background-color: color-mix(in srgb, var(--paper) 86%, transparent);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">

            <a href="{{ route('home') }}" class="flex items-center gap-2.5 {{ $focus }} rounded-lg" aria-label="{{ $siteName }} home">
                <span class="w-9 h-9 rounded-xl bg-accent text-white flex items-center justify-center shadow-soft">
                    @include('marketing._icon', ['name' => 'mail', 'class' => 'w-5 h-5'])
                </span>
                <span class="text-xl font-extrabold tracking-tight">{{ $siteName }}</span>
            </a>

            <!-- Desktop navigation -->
            <nav class="hidden lg:flex items-center gap-1" aria-label="Main">
                <div class="relative" @mouseenter="mega = true" @mouseleave="mega = false" @click.outside="mega = false">
                    <button type="button" @click="mega = !mega" :aria-expanded="mega.toString()" aria-haspopup="true"
                            class="flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-semibold hover:bg-paper-tint {{ $focus }} {{ request()->routeIs('marketing.features', 'marketing.audience') ? 'text-accent' : '' }}">
                        Features
                        <span :class="mega ? 'rotate-180' : ''" class="transition-transform">@include('marketing._icon', ['name' => 'chevron', 'class' => 'w-4 h-4'])</span>
                    </button>

                    <div x-show="mega" x-cloak x-transition.opacity.duration.150ms class="absolute left-1/2 -translate-x-1/2 top-full pt-3 w-[46rem]">
                        <div class="rounded-2xl border border-border bg-card shadow-2xl p-3">
                            <div class="grid grid-cols-2 gap-x-4">
                                <div>
                                    <div class="px-3 pt-2 pb-1.5 text-[11px] font-bold uppercase tracking-[0.16em] text-muted">Product</div>
                                    @foreach (config('marketing.menu_capabilities') as $key)
                                        <a href="{{ route('marketing.features') }}#{{ $key }}" class="flex items-start gap-3 p-3 rounded-xl hover:bg-paper-tint {{ $focus }}">
                                            <span class="mt-0.5 w-9 h-9 shrink-0 rounded-lg bg-accent/10 text-accent flex items-center justify-center">@include('marketing._icon', ['name' => $caps[$key]['icon']])</span>
                                            <span><span class="block text-sm font-bold">{{ $caps[$key]['name'] }}</span><span class="block text-xs text-muted leading-snug">{{ \Illuminate\Support\Str::limit($caps[$key]['summary'], 62) }}</span></span>
                                        </a>
                                    @endforeach
                                </div>
                                <div>
                                    <div class="px-3 pt-2 pb-1.5 text-[11px] font-bold uppercase tracking-[0.16em] text-muted">Solutions</div>
                                    @foreach ($auds as $slug => $aud)
                                        <a href="{{ route('marketing.audience', $slug) }}" class="flex items-start gap-3 p-3 rounded-xl hover:bg-paper-tint {{ $focus }}">
                                            <span class="mt-0.5 w-9 h-9 shrink-0 rounded-lg bg-sun/20 text-ink flex items-center justify-center">@include('marketing._icon', ['name' => $aud['icon']])</span>
                                            <span><span class="block text-sm font-bold">{{ $aud['name'] }}</span><span class="block text-xs text-muted leading-snug">{{ $aud['menu'] }}</span></span>
                                        </a>
                                    @endforeach
                                    <a href="{{ route('marketing.features') }}" class="mt-2 flex items-center justify-between rounded-xl bg-sidebar text-white px-4 py-3 text-sm font-bold hover:bg-black {{ $focus }}">
                                        See everything AutoMail does
                                        @include('marketing._icon', ['name' => 'arrow', 'class' => 'w-4 h-4'])
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="{{ route('marketing.pricing') }}" class="px-3.5 py-2 rounded-lg text-sm font-semibold hover:bg-paper-tint {{ $focus }} {{ request()->routeIs('marketing.pricing') ? 'text-accent' : '' }}">Pricing</a>
                <a href="{{ route('marketing.audience', 'developers') }}" class="px-3.5 py-2 rounded-lg text-sm font-semibold hover:bg-paper-tint {{ $focus }}">Developers</a>
            </nav>

            <div class="flex items-center gap-2">
                <button type="button" @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); try { localStorage.setItem('automail-theme', dark ? 'dark' : 'light'); } catch (e) {}"
                        class="p-2 rounded-lg hover:bg-paper-tint {{ $focus }}" :aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'">
                    <span x-show="!dark">@include('marketing._icon', ['name' => 'moon', 'class' => 'w-5 h-5'])</span>
                    <span x-show="dark" x-cloak>@include('marketing._icon', ['name' => 'sun', 'class' => 'w-5 h-5'])</span>
                </button>

                @auth
                    <a href="{{ route('dashboard') }}" class="hidden sm:inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-accent text-white text-sm font-bold shadow-soft hover:opacity-90 transition {{ $focus }}">Open dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="hidden sm:inline-flex px-3.5 py-2 rounded-lg text-sm font-semibold hover:bg-paper-tint {{ $focus }}">Log in</a>
                    <a href="{{ route('register') }}" class="hidden sm:inline-flex items-center px-4 py-2 rounded-lg bg-accent text-white text-sm font-bold shadow-soft hover:opacity-90 transition {{ $focus }}">Start free</a>
                @endauth

                <button type="button" @click="menu = !menu" :aria-expanded="menu.toString()" aria-controls="mobile-menu" class="lg:hidden p-2 rounded-lg hover:bg-paper-tint {{ $focus }}" aria-label="Menu">
                    <span x-show="!menu">@include('marketing._icon', ['name' => 'menu', 'class' => 'w-6 h-6'])</span>
                    <span x-show="menu" x-cloak>@include('marketing._icon', ['name' => 'close', 'class' => 'w-6 h-6'])</span>
                </button>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="mobile-menu" x-show="menu" x-cloak x-transition.opacity class="lg:hidden border-t border-border bg-card max-h-[calc(100vh-4rem)] overflow-y-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 space-y-5">
                <div>
                    <div class="px-2 pb-1 text-[11px] font-bold uppercase tracking-[0.16em] text-muted">Product</div>
                    @foreach (config('marketing.menu_capabilities') as $key)
                        <a href="{{ route('marketing.features') }}#{{ $key }}" @click="menu = false" class="flex items-center gap-3 px-2 py-2.5 rounded-lg hover:bg-paper-tint">
                            <span class="w-8 h-8 rounded-lg bg-accent/10 text-accent flex items-center justify-center">@include('marketing._icon', ['name' => $caps[$key]['icon'], 'class' => 'w-4 h-4'])</span>
                            <span class="text-sm font-semibold">{{ $caps[$key]['name'] }}</span>
                        </a>
                    @endforeach
                </div>
                <div>
                    <div class="px-2 pb-1 text-[11px] font-bold uppercase tracking-[0.16em] text-muted">Solutions</div>
                    @foreach ($auds as $slug => $aud)
                        <a href="{{ route('marketing.audience', $slug) }}" class="flex items-center gap-3 px-2 py-2.5 rounded-lg hover:bg-paper-tint">
                            <span class="w-8 h-8 rounded-lg bg-sun/20 flex items-center justify-center">@include('marketing._icon', ['name' => $aud['icon'], 'class' => 'w-4 h-4'])</span>
                            <span class="text-sm font-semibold">{{ $aud['name'] }}</span>
                        </a>
                    @endforeach
                </div>
                <div class="border-t border-border pt-4 grid gap-2">
                    <a href="{{ route('marketing.pricing') }}" class="px-2 py-2 text-sm font-semibold">Pricing</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-center px-4 py-3 rounded-lg bg-accent text-white text-sm font-bold">Open dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-center px-4 py-3 rounded-lg border border-border text-sm font-semibold">Log in</a>
                        <a href="{{ route('register') }}" class="text-center px-4 py-3 rounded-lg bg-accent text-white text-sm font-bold">Start free</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <main id="main">
        @yield('content')
    </main>

    <!-- ============================== Footer ============================== -->
    <footer class="bg-sidebar text-sidebar-text">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-10">
                <div class="col-span-2">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-accent text-white flex items-center justify-center">@include('marketing._icon', ['name' => 'mail', 'class' => 'w-5 h-5'])</span>
                        <span class="text-xl font-extrabold tracking-tight text-white">{{ $siteName }}</span>
                    </div>
                    <p class="mt-4 text-sm leading-relaxed max-w-sm">{{ config('marketing.tagline') }} Grow your audience, design emails, automate journeys and see what works, all in one place.</p>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-[0.16em] text-white">Product</div>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li><a class="hover:text-white" href="{{ route('marketing.features') }}">Features</a></li>
                        @foreach (config('marketing.menu_capabilities') as $key)
                            <li><a class="hover:text-white" href="{{ route('marketing.features') }}#{{ $key }}">{{ $caps[$key]['name'] }}</a></li>
                        @endforeach
                        <li><a class="hover:text-white" href="{{ route('marketing.pricing') }}">Pricing</a></li>
                    </ul>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-[0.16em] text-white">Solutions</div>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        @foreach ($auds as $slug => $aud)
                            <li><a class="hover:text-white" href="{{ route('marketing.audience', $slug) }}">{{ $aud['name'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-[0.16em] text-white">Get started</div>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        @auth
                            <li><a class="hover:text-white" href="{{ route('dashboard') }}">Open dashboard</a></li>
                        @else
                            <li><a class="hover:text-white" href="{{ route('register') }}">Create a free account</a></li>
                            <li><a class="hover:text-white" href="{{ route('login') }}">Log in</a></li>
                        @endauth
                    </ul>
                </div>
            </div>
            <div class="mt-12 pt-6 border-t border-sidebar-line text-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <span>&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</span>
                <span>Every plan includes every feature.</span>
            </div>
        </div>
    </footer>

    <script>
        // Reveal sections as they scroll into view (skipped when the visitor prefers less motion).
        (function () {
            var items = document.querySelectorAll('[data-reveal]');
            if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                items.forEach(function (el) { el.classList.add('is-in'); });
                return;
            }
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) { entry.target.classList.add('is-in'); io.unobserve(entry.target); }
                });
            }, { threshold: 0.12 });
            items.forEach(function (el) { io.observe(el); });
        })();
    </script>
</body>
</html>
