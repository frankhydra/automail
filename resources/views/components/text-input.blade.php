@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-card border-border text-ink focus:border-accent focus:ring-accent rounded-md shadow-sm disabled:opacity-50']) }}>
