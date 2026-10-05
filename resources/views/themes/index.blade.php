{{--
    Theme picker. Each card is a tiny form: clicking it applies the theme instantly
    (data-palette on <html>) and saves it to the user's account.
    The preview swatches use plain hex values from config/themes.php so every card
    looks correct no matter which theme is currently active.
--}}
<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-ink">Theme</h1>
                <p class="mt-1 text-sm text-muted max-w-2xl">
                    Pick the colours AutoMail uses for you. Dark mode still works on top of every theme
                    (use the toggle in the sidebar). This only changes how the app looks for your account;
                    your emails and your teammates' screens are not affected.
                </p>
            </div>

            <x-flash-messages />

            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($palettes as $key => $palette)
                    @php
                        $p = $palette['preview'];
                        $selected = $key === $current;
                    @endphp
                    <form method="POST" action="{{ route('themes.update') }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="palette" value="{{ $key }}">

                        <button type="submit"
                                @click="localStorage.setItem('automail-palette', '{{ $key }}'); document.documentElement.setAttribute('data-palette', '{{ $key }}')"
                                aria-pressed="{{ $selected ? 'true' : 'false' }}"
                                class="w-full text-left rounded-2xl bg-card border-2 p-4 shadow-soft transition focus:outline-none focus:ring-2 focus:ring-accent {{ $selected ? 'border-accent' : 'border-border hover:border-accent' }}">

                            {{-- Mini app preview: light on the left, dark on the right --}}
                            <div class="flex rounded-xl overflow-hidden border" style="border-color: {{ $p['border'] }}; height: 104px;">
                                @foreach ([['bg' => $p['paper'], 'card' => $p['card'], 'line' => $p['border']], ['bg' => $p['dark_paper'], 'card' => $p['dark_card'], 'line' => $p['dark_card']]] as $half)
                                    <div class="flex flex-1" style="background: {{ $half['bg'] }};">
                                        <div class="w-1/4 p-1.5 space-y-1" style="background: {{ $p['sidebar'] }};">
                                            <div class="h-2 rounded-sm" style="background: {{ $p['accent'] }};"></div>
                                            <div class="h-1.5 rounded-sm" style="background: rgba(255,255,255,0.18);"></div>
                                            <div class="h-1.5 rounded-sm" style="background: rgba(255,255,255,0.18);"></div>
                                            <div class="h-1.5 rounded-sm" style="background: rgba(255,255,255,0.18);"></div>
                                        </div>
                                        <div class="flex-1 p-2 space-y-1.5">
                                            <div class="h-5 rounded-md" style="background: {{ $p['accent'] }};"></div>
                                            <div class="rounded-md p-1.5 space-y-1" style="background: {{ $half['card'] }}; border: 1px solid {{ $half['line'] }};">
                                                <div class="h-1.5 w-3/4 rounded-sm" style="background: {{ $p['accent'] }}; opacity: .55;"></div>
                                                <div class="h-1.5 w-1/2 rounded-sm" style="background: {{ $p['sun'] }};"></div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-3 flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="font-bold text-ink">{{ $palette['name'] }}</div>
                                    <p class="text-xs text-muted mt-0.5">{{ $palette['tagline'] }}</p>
                                </div>
                                @if ($selected)
                                    <span class="shrink-0 inline-flex items-center gap-1 text-[11px] font-bold uppercase tracking-wider px-2 py-1 rounded-full bg-accent text-white">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        Active
                                    </span>
                                @endif
                            </div>

                            <div class="mt-3 flex items-center gap-1.5">
                                @foreach ([$p['accent'], $p['sun'], $p['sidebar'], $p['paper']] as $swatch)
                                    <span class="w-5 h-5 rounded-full border border-border" style="background: {{ $swatch }};"></span>
                                @endforeach
                            </div>
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
