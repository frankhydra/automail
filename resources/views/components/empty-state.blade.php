@props(['title', 'description' => null, 'actionLabel' => null, 'actionHref' => null])

{{--
    A consistent "nothing here yet" block, used instead of an empty table with
    no explanation. Usage:
    <x-empty-state title="No campaigns yet" description="..." action-label="Create Campaign" :action-href="route('campaigns.create')" />
--}}
<div class="text-center py-16 px-6">
    <div class="mx-auto w-12 h-12 rounded-full bg-paper-tint dark:bg-white/5 flex items-center justify-center mb-4">
        <span class="w-3 h-3 rounded-full bg-wax"></span>
    </div>
    <h3 class="text-base font-semibold text-ink">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 text-sm text-muted max-w-sm mx-auto">{{ $description }}</p>
    @endif
    @if ($actionLabel && $actionHref)
        <a href="{{ $actionHref }}" class="mt-5 inline-flex items-center px-4 py-2 bg-accent text-paper rounded-md font-semibold text-xs uppercase tracking-widest hover:opacity-90 transition">
            {{ $actionLabel }}
        </a>
    @endif
</div>
