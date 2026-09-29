{{--
    Left sidebar. Included inside the x-data="{ sidebarOpen: false }" wrapper in
    layouts/app.blade.php, so the mobile overlay/toggle below shares that state.
--}}

<!-- Mobile overlay backdrop -->
<div x-show="sidebarOpen" x-cloak x-transition.opacity
     class="fixed inset-0 bg-ink/40 z-30 md:hidden"
     @click="sidebarOpen = false"></div>

<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
    class="fixed md:sticky top-0 inset-y-0 left-0 z-40 w-64 h-screen bg-card border-r border-border flex flex-col shrink-0 transition-transform duration-200 ease-in-out">

    <!-- Brand -->
    <div class="h-16 flex items-center justify-between px-5 border-b border-border shrink-0">
        <a href="{{ route('dashboard') }}" class="font-extrabold text-xl tracking-tight text-accent flex items-center gap-2">
            <span class="w-3.5 h-3.5 rounded-full bg-wax inline-block shadow-sm"></span>
            AutoMail
        </a>
        <button @click="sidebarOpen = false" class="md:hidden text-muted hover:text-ink">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
    </div>

    <!-- Primary nav -->
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
        <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
            <span>Dashboard</span>
        </x-sidebar-link>

        <x-sidebar-link :href="route('contacts.import.show')" :active="request()->routeIs('contacts.import.*')">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
            <span>Import Contacts</span>
        </x-sidebar-link>

        <x-sidebar-link :href="route('sending-identities.index')" :active="request()->routeIs('sending-identities.*')">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0-.414.336-.75.75-.75h18a.75.75 0 01.75.75v10.5a.75.75 0 01-.75.75H3a.75.75 0 01-.75-.75V6.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75l9.75 6.75 9.75-6.75" /></svg>
            <span>Sending Identities</span>
        </x-sidebar-link>

        <x-sidebar-link :href="route('templates.index')" :active="request()->routeIs('templates.*')">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-1.519-3.129l-3.712-3.712M6 20.25h12A2.25 2.25 0 0020.25 18V9.75L14.25 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" /></svg>
            <span>Templates</span>
        </x-sidebar-link>

        <x-sidebar-link :href="route('segments.index')" :active="request()->routeIs('segments.*')">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" /></svg>
            <span>Segments</span>
        </x-sidebar-link>

        <x-sidebar-link :href="route('campaigns.index')" :active="request()->routeIs('campaigns.*')">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.769 59.769 0 0121.485 12 59.768 59.768 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
            <span>Campaigns</span>
        </x-sidebar-link>
    </nav>

    <!-- Bottom: dark mode toggle, settings, user, logout -->
    <div class="border-t border-border p-3 space-y-1 shrink-0"
         x-data="{ dark: document.documentElement.classList.contains('dark') }"
         x-init="$watch('dark', value => { document.documentElement.classList.toggle('dark', value); localStorage.setItem('automail-theme', value ? 'dark' : 'light'); })">

        <button @click="dark = !dark" type="button" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium text-muted hover:bg-paper-tint hover:text-ink transition">
            <span x-show="!dark" x-cloak class="w-5 h-5 shrink-0">&#9789;</span>
            <span x-show="dark" x-cloak class="w-5 h-5 shrink-0">&#9788;</span>
            <span x-text="dark ? 'Light mode' : 'Dark mode'"></span>
        </button>

        <x-sidebar-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit') || request()->routeIs('team.*') || request()->routeIs('billing.*') || request()->routeIs('api-tokens.*')">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            <span>Settings</span>
        </x-sidebar-link>

        <div class="flex items-center gap-3 px-3 py-2.5">
            <div class="w-8 h-8 rounded-full bg-accent text-paper flex items-center justify-center text-xs font-bold shrink-0">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-sm font-semibold text-ink truncate">{{ Auth::user()->name }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Log out" class="text-muted hover:text-danger transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H3" /></svg>
                </button>
            </form>
        </div>
    </div>
</aside>
