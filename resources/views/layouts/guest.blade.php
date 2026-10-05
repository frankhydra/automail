<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @auth data-palette="{{ \App\Support\Theme::current(Auth::user()) }}" @endauth>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'AutoMail') }}</title>

        <!-- Dark mode: applied before paint to avoid a flash of the wrong theme. -->
        <script>
            (function () {
                var stored = localStorage.getItem('automail-theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (stored === 'dark' || (!stored && prefersDark)) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        <!-- Scripts -->
        @include('layouts._palette')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased">
        <div class="min-h-screen flex bg-paper">

            <!-- Brand panel: hidden on small screens, shown from md up -->
            <div class="hidden md:flex md:w-1/2 lg:w-2/5 bg-ink text-paper flex-col justify-between p-12 relative overflow-hidden">
                <!-- Soft glow accent, purely decorative -->
                <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-wax opacity-20 blur-3xl"></div>
                <div class="absolute -bottom-32 -left-16 w-96 h-96 rounded-full bg-accent opacity-30 blur-3xl"></div>

                <a href="/" class="relative font-extrabold text-2xl tracking-tight flex items-center gap-2">
                    <span class="w-4 h-4 rounded-full bg-wax inline-block"></span>
                    AutoMail
                </a>

                <div class="relative">
                    <h1 class="text-4xl font-extrabold leading-tight mb-4">
                        Email marketing,<br>
                        <span class="text-wax">built to be reliable.</span>
                    </h1>
                    <p class="text-paper/70 max-w-sm">
                        Verified sending domains, real deliverability tracking, and automation that
                        respects your subscribers.
                    </p>

                    <ul class="mt-8 space-y-3">
                        @foreach ([
                            'DNS-verified sending identities',
                            'Segment your audience with real rules',
                            'Automation, without the busywork',
                        ] as $feature)
                            <li class="flex items-center gap-3 text-sm text-paper/90">
                                <span class="w-5 h-5 rounded-full bg-paper/10 flex items-center justify-center shrink-0">
                                    <svg class="w-3 h-3 text-wax" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                </span>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="relative text-xs text-paper/50">&copy; {{ date('Y') }} AutoMail</p>
            </div>

            <!-- Form panel -->
            <div class="flex-1 flex flex-col justify-center items-center px-6 py-12">
                <!-- Mobile-only brand mark, since the panel above is hidden here -->
                <a href="/" class="md:hidden mb-8 font-extrabold text-2xl tracking-tight text-accent flex items-center gap-2">
                    <span class="w-4 h-4 rounded-full bg-wax inline-block"></span>
                    AutoMail
                </a>

                <div class="w-full sm:max-w-md px-6 py-8 bg-card border border-border shadow-sm rounded-lg">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
