<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-card border border-border rounded-md font-semibold text-xs text-ink uppercase tracking-widest shadow-sm hover:bg-paper-tint focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 disabled:opacity-40 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
