@props(['active' => false])

@php
$classes = ($active ?? false)
    ? 'flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-semibold bg-accent text-paper transition'
    : 'flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium text-muted hover:bg-paper-tint hover:text-ink transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
