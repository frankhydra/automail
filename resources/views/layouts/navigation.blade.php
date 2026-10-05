{{--
    Charcoal left sidebar. Included inside the x-data="{ sidebarOpen: false }" wrapper in
    layouts/app.blade.php. Items with :disabled="true" are screens planned for later milestones.
--}}
@php
    $navOrg = Auth::user()->currentOrganization();
    $navOrgName = $navOrg?->name ?? Auth::user()->name;
    $navPlan = ucfirst($navOrg?->plan ?? 'free');

    // Heroicons (outline) path data, one place for all sidebar icons.
    $paths = [
        'dashboard' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
        'campaigns' => 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0l-9.75 6.75L2.25 6.75',
        'builder' => 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125',
        'audience' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
        'automations' => 'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5',
        'analytics' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
        'studio' => 'M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z',
        'integrations' => 'M13.5 16.875h3.375m0 0h3.375m-3.375 0V13.5m0 3.375v3.375M6 10.5h2.25a2.25 2.25 0 002.25-2.25V6a2.25 2.25 0 00-2.25-2.25H6A2.25 2.25 0 003.75 6v2.25A2.25 2.25 0 006 10.5zm0 9.75h2.25A2.25 2.25 0 0010.5 18v-2.25a2.25 2.25 0 00-2.25-2.25H6a2.25 2.25 0 00-2.25 2.25V18A2.25 2.25 0 006 20.25zm9.75-9.75H18a2.25 2.25 0 002.25-2.25V6A2.25 2.25 0 0018 3.75h-2.25A2.25 2.25 0 0013.5 6v2.25a2.25 2.25 0 002.25 2.25z',
        'import' => 'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3',
        'segments' => 'M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z',
        'identities' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
        'settings' => 'M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894zM15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'logout' => 'M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H3',
    ];
    $ico = fn (string $name) => '<svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="'.$paths[$name].'"/></svg>';
@endphp

<div x-show="sidebarOpen" x-cloak x-transition.opacity
     class="fixed inset-0 bg-black/50 z-30 md:hidden"
     @click="sidebarOpen = false"></div>

<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
    class="fixed md:sticky top-0 inset-y-0 left-0 z-40 w-64 h-screen bg-sidebar border-r border-sidebar-line flex flex-col shrink-0 transition-transform duration-200 ease-in-out">

    <!-- Brand -->
    <div class="h-16 flex items-center justify-between px-5 shrink-0">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
            <span class="w-8 h-8 rounded-lg bg-accent flex items-center justify-center text-white">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l9 6 9-6M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
            </span>
            <span class="leading-tight">
                <span class="block font-extrabold text-lg tracking-tight text-white">AutoMail</span>
                <span class="block text-[10px] uppercase tracking-[0.18em] text-sidebar-text/60">Marketing</span>
            </span>
        </a>
        <button @click="sidebarOpen = false" class="md:hidden text-sidebar-text hover:text-white">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
    </div>

    <div class="px-4 pb-3 shrink-0">
        <a href="{{ route('campaigns.create') }}"
           class="flex items-center justify-center gap-2 w-full py-2.5 rounded-lg bg-sun text-sidebar font-bold text-sm hover:brightness-105 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Create Campaign
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto py-2 px-3 space-y-1">
        <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">{!! $ico('dashboard') !!}<span>Dashboard</span></x-sidebar-link>
        <x-sidebar-link :href="route('campaigns.index')" :active="request()->routeIs('campaigns.*')">{!! $ico('campaigns') !!}<span>Campaigns</span></x-sidebar-link>
        <x-sidebar-link :href="route('templates.index')" :active="request()->routeIs('templates.*')">{!! $ico('builder') !!}<span>Email Builder</span></x-sidebar-link>
        <x-sidebar-link :href="route('contacts.index')" :active="request()->routeIs('contacts.index') || request()->routeIs('contacts.edit')">{!! $ico('audience') !!}<span>Audience</span></x-sidebar-link>
        <x-sidebar-link :href="route('automations.index')" :active="request()->routeIs('automations.*')">{!! $ico('automations') !!}<span>Automations</span></x-sidebar-link>
        <x-sidebar-link :href="route('analytics.index')" :active="request()->routeIs('analytics.*')">{!! $ico('analytics') !!}<span>Analytics</span></x-sidebar-link>
        <x-sidebar-link :href="route('assets.index')" :active="request()->routeIs('assets.*')">{!! $ico('studio') !!}<span>Content Studio</span></x-sidebar-link>
        <x-sidebar-link :disabled="true">{!! $ico('integrations') !!}<span>Integrations</span></x-sidebar-link>

        <div class="pt-4 pb-1 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-sidebar-text/40">Manage</div>

        <x-sidebar-link :href="route('contacts.import.show')" :active="request()->routeIs('contacts.import.*')">{!! $ico('import') !!}<span>Import Contacts</span></x-sidebar-link>
        <x-sidebar-link :href="route('segments.index')" :active="request()->routeIs('segments.*')">{!! $ico('segments') !!}<span>Segments</span></x-sidebar-link>
        <x-sidebar-link :href="route('sending-identities.index')" :active="request()->routeIs('sending-identities.*')">{!! $ico('identities') !!}<span>Sending Identities</span></x-sidebar-link>
    </nav>

    <div class="border-t border-sidebar-line p-3 space-y-1 shrink-0"
         x-data="{ dark: document.documentElement.classList.contains('dark') }"
         x-init="$watch('dark', value => { document.documentElement.classList.toggle('dark', value); localStorage.setItem('automail-theme', value ? 'dark' : 'light'); })">

        <button @click="dark = !dark" type="button" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-sidebar-text hover:bg-sidebar-hover hover:text-white transition">
            <span x-show="!dark" x-cloak class="w-5 h-5 shrink-0 text-center">&#9789;</span>
            <span x-show="dark" x-cloak class="w-5 h-5 shrink-0 text-center">&#9788;</span>
            <span x-text="dark ? 'Light mode' : 'Dark mode'"></span>
        </button>

        <x-sidebar-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit') || request()->routeIs('team.*') || request()->routeIs('billing.*') || request()->routeIs('api-tokens.*')">{!! $ico('settings') !!}<span>Settings</span></x-sidebar-link>

        <div class="flex items-center gap-3 px-3 py-2.5">
            <div class="w-9 h-9 rounded-full bg-accent text-white flex items-center justify-center text-xs font-bold shrink-0">{{ strtoupper(substr($navOrgName, 0, 2)) }}</div>
            <div class="min-w-0 flex-1">
                <div class="text-sm font-semibold text-white truncate">{{ $navOrgName }}</div>
                <div class="text-[11px] text-sidebar-text/60 truncate">{{ $navPlan }} plan</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Log out" class="text-sidebar-text/60 hover:text-white transition">{!! $ico('logout') !!}</button>
            </form>
        </div>
    </div>
</aside>
