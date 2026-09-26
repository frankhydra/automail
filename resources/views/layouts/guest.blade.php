<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased bg-paper">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-paper">
            <div>
                <a href="/" class="font-extrabold text-2xl tracking-tight text-accent flex items-center gap-2">
                    <span class="w-4 h-4 rounded-full bg-wax inline-block shadow-sm"></span>
                    AutoMail
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-card border border-border shadow-sm overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
