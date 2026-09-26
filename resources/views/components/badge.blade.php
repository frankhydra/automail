@props(['status'])

@php
    // Maps every status string used across campaigns, recipients, and sending
    // identities to one of four semantic tones - so "Sent" and "Verified" always
    // look the same shade of success-green, "Failed" and "Bounced" the same
    // shade of danger-red, everywhere in the app, instead of each page picking
    // its own ad-hoc color for the same meaning.
    $tone = match (strtolower((string) $status)) {
        'sent', 'verified', 'subscribed', 'delivered' => 'success',
        'scheduled', 'pending', 'queued', 'sending' => 'warning',
        'failed', 'bounced', 'complained', 'cancelled', 'unsubscribed' => 'danger',
        'draft', 'skipped', 'suppressed' => 'info',
        default => 'info',
    };

    $classes = [
        'success' => 'bg-success-tint text-success dark:bg-success/20 dark:text-success',
        'warning' => 'bg-warning-tint text-warning dark:bg-warning/20 dark:text-warning',
        'danger' => 'bg-danger-tint text-danger dark:bg-danger/20 dark:text-danger',
        'info' => 'bg-paper-tint text-muted dark:bg-white/10 dark:text-ink',
    ][$tone];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {$classes}"]) }}>
    {{ $slot->isEmpty() ? ucfirst((string) $status) : $slot }}
</span>
