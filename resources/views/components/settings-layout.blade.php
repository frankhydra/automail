@props(['active' => null])

{{--
    Shared visual frame for Profile, Team, Billing, and API Access - four
    existing, unchanged routes/controllers, now presented as tabs of one
    "Settings" area instead of four unrelated pages. Usage:
    <x-settings-layout active="team"> ...page content... </x-settings-layout>
--}}

@php
    $tabs = [
        'profile' => ['label' => 'Profile', 'route' => 'profile.edit'],
        'team' => ['label' => 'Team', 'route' => 'team.index'],
        'billing' => ['label' => 'Billing', 'route' => 'billing.index'],
        'api' => ['label' => 'API Access', 'route' => 'api-tokens.index'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">
            {{ __('Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">

                <aside class="md:col-span-1">
                    <nav class="space-y-1">
                        @foreach ($tabs as $key => $tab)
                            <a href="{{ route($tab['route']) }}"
                               class="block px-3 py-2 rounded-md text-sm font-semibold transition {{ $active === $key ? 'bg-accent text-paper' : 'text-muted hover:bg-paper-tint hover:text-ink' }}">
                                {{ $tab['label'] }}
                            </a>
                        @endforeach
                    </nav>
                </aside>

                <div class="md:col-span-3 space-y-6">
                    <x-flash-messages />
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
