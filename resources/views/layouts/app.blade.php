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

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-paper text-ink selection:bg-accent selection:text-white">
        <div class="min-h-screen flex" x-data="{ sidebarOpen: false }">
            @include('layouts.navigation')

            <div class="flex-1 min-w-0 flex flex-col">
                <!-- Top bar: search (uses the existing contact search) + workspace pill -->
                <div class="flex items-center gap-3 bg-card border-b border-border px-4 sm:px-6 h-16 shrink-0">
                    <button @click="sidebarOpen = true" class="md:hidden text-ink" aria-label="Open menu">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>

                    <form method="GET" action="{{ route('contacts.index') }}" class="flex-1 max-w-xl">
                        <div class="relative">
                            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 110-15 7.5 7.5 0 010 15z"/></svg>
                            <input type="text" name="search" placeholder="Search contacts by name, email or tag..."
                                   class="w-full pl-9 pr-3 py-2 text-sm rounded-lg bg-paper border-border text-ink placeholder:text-muted focus:border-accent focus:ring-accent">
                        </div>
                    </form>

                    <div class="ml-auto hidden sm:flex items-center gap-2 text-xs font-semibold text-muted bg-paper-tint border border-border rounded-full px-3 py-1.5">
                        <span class="w-2 h-2 rounded-full bg-success"></span>
                        {{ Auth::user()->currentOrganization()?->name ?? 'No workspace' }}
                    </div>
                </div>

                @isset($header)
                    <header class="bg-card border-b border-border">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="flex-1">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
