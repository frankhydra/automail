<nav x-data="{ open: false }" class="bg-card border-b border-border shadow-sm">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Brand Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="font-extrabold text-xl tracking-tight text-accent flex items-center gap-2">
                        <span class="w-3.5 h-3.5 rounded-full bg-wax inline-block shadow-sm"></span>
                        AutoMail
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="text-ink hover:text-accent border-accent font-semibold">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('contacts.import.show')" :active="request()->routeIs('contacts.import.*')" class="text-ink hover:text-accent border-accent font-semibold">
                        {{ __('Import Contacts') }}
                    </x-nav-link>
                    <x-nav-link :href="route('sending-identities.index')" :active="request()->routeIs('sending-identities.*')" class="text-ink hover:text-accent border-accent font-semibold">
                        {{ __('Sending Identities') }}
                    </x-nav-link>
                    <x-nav-link :href="route('templates.index')" :active="request()->routeIs('templates.*')" class="text-ink hover:text-accent border-accent font-semibold">
                        {{ __('Templates') }}
                    </x-nav-link>
                    <x-nav-link :href="route('campaigns.index')" :active="request()->routeIs('campaigns.*')" class="text-ink hover:text-accent border-accent font-semibold">
                        {{ __('Campaigns') }}
                    </x-nav-link>
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-border text-sm leading-4 font-semibold rounded-md text-ink bg-paper-tint hover:bg-paper focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4 text-muted" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')" class="text-ink hover:bg-paper-tint">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();" class="text-wax font-semibold hover:bg-paper-tint">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger Button -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-muted hover:text-ink hover:bg-paper-tint focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'inline-flex': ! open, 'hidden': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-card border-t border-border">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('contacts.import.show')" :active="request()->routeIs('contacts.import.*')">
                {{ __('Import Contacts') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('sending-identities.index')" :active="request()->routeIs('sending-identities.*')">
                {{ __('Sending Identities') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('templates.index')" :active="request()->routeIs('templates.*')">
                {{ __('Templates') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('campaigns.index')" :active="request()->routeIs('campaigns.*')">
                {{ __('Campaigns') }}
            </x-responsive-nav-link>
        </div>
    </div>
</nav>