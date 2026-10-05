@props(['active' => false, 'disabled' => false])

{{--
    Sidebar item on the charcoal sidebar.
    - active:   rust pill
    - disabled: greyed out with a "Soon" tag (screens built in later milestones)
--}}
@if ($disabled)
    <span title="Coming soon" {{ $attributes->merge(['class' => 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-sidebar-text/40 cursor-not-allowed select-none']) }}>
        {{ $slot }}
        <span class="ml-auto text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-sidebar-hover text-sidebar-text/60">Soon</span>
    </span>
@else
    @php
        $classes = ($active ?? false)
            ? 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold bg-accent text-white shadow-sm transition'
            : 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-sidebar-text hover:bg-sidebar-hover hover:text-white transition';
    @endphp
    <a {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@endif
