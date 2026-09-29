<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'AutoMail') }}</title>

        <!-- Dark mode: applied before paint to avoid a flash of the wrong theme.
             Reads a saved preference, or falls back to the OS setting once. -->
        <script>
            (function () {
                var stored = localStorage.getItem('automail-theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (stored === 'dark' || (!stored && prefersDark)) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-paper text-ink selection:bg-accent selection:text-paper">
        <div class="min-h-screen flex" x-data="{ sidebarOpen: false }">
            @include('layouts.navigation')

            <div class="flex-1 min-w-0 flex flex-col">
                <!-- Mobile top bar: sidebar is off-canvas below md, opened via this button -->
                <div class="md:hidden flex items-center justify-between bg-card border-b border-border px-4 h-14 shrink-0">
                    <a href="{{ route('dashboard') }}" class="font-extrabold text-lg tracking-tight text-accent flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-wax inline-block"></span>
                        AutoMail
                    </a>
                    <button @click="sidebarOpen = true" class="text-ink">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                </div>

                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-card shadow-sm border-b border-border">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="flex-1">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
