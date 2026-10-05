@php
    $statusStyles = [
        'draft' => 'bg-paper-tint text-muted',
        'active' => 'bg-success-tint text-success',
        'paused' => 'bg-warning-tint text-warning',
    ];
@endphp

<x-app-layout>
    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-ink">Customer Journeys</h1>
                    <p class="text-sm text-muted mt-1">Trigger multi-step email workflows based on what your contacts do.</p>
                </div>
                @if ($canEdit)
                    <a href="{{ route('automations.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-accent text-white text-sm font-bold shadow-soft hover:opacity-90 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        New Journey
                    </a>
                @endif
            </div>

            <x-flash-messages />

            @if ($errors->has('activate'))
                <div class="p-4 bg-danger-tint border border-danger/30 text-danger rounded-lg text-sm">{{ $errors->first('activate') }}</div>
            @endif

            <div class="p-4 bg-paper-tint border border-border rounded-lg text-xs text-muted">
                Journeys are advanced by a background process. Locally, keep <code>php artisan schedule:work</code> running (and a queue worker if your queue is not <code>sync</code>). In production this needs the usual cron entry for <code>schedule:run</code>.
            </div>

            @if ($automations->isEmpty())
                <div class="bg-card border border-border shadow-soft rounded-xl">
                    <x-empty-state title="No journeys yet"
                        description="Build a welcome series: send an email when someone joins, wait two days, then follow up based on whether they opened it."
                        :action-label="$canEdit ? 'New Journey' : null" :action-href="$canEdit ? route('automations.create') : null" />
                </div>
            @else
                <div class="bg-card border border-border rounded-xl shadow-soft overflow-hidden">
                    <ul class="divide-y divide-border">
                        @foreach ($automations as $automation)
                            <li class="px-6 py-5 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('automations.edit', $automation->id) }}" class="text-lg font-extrabold text-ink hover:text-accent truncate">{{ $automation->name }}</a>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold capitalize {{ $statusStyles[$automation->status] ?? '' }}">{{ $automation->status }}</span>
                                    </div>
                                    <div class="text-sm text-muted mt-1">Starts when: {{ $automation->triggerLabel() }}</div>
                                    <div class="text-xs text-muted mt-1">{{ $automation->email_steps }} email{{ $automation->email_steps == 1 ? '' : 's' }}</div>
                                </div>

                                <div class="flex flex-wrap items-center gap-6 shrink-0">
                                    <div class="text-center"><div class="text-lg font-extrabold text-ink">{{ number_format($automation->total_runs) }}</div><div class="text-[10px] font-bold uppercase tracking-wider text-muted">Entered</div></div>
                                    <div class="text-center"><div class="text-lg font-extrabold text-ink">{{ number_format($automation->active_runs) }}</div><div class="text-[10px] font-bold uppercase tracking-wider text-muted">In progress</div></div>
                                    <div class="text-center"><div class="text-lg font-extrabold text-ink">{{ number_format($automation->completed_runs) }}</div><div class="text-[10px] font-bold uppercase tracking-wider text-muted">Completed</div></div>

                                    <div class="flex items-center gap-2">
                                        @if ($canActivate)
                                            @if ($automation->status === 'active')
                                                <form method="POST" action="{{ route('automations.pause', $automation->id) }}">@csrf
                                                    <button type="submit" class="px-3 py-2 rounded-lg border border-border bg-paper-tint text-sm font-semibold text-ink hover:border-accent">Pause</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('automations.activate', $automation->id) }}">@csrf
                                                    <button type="submit" class="px-3 py-2 rounded-lg bg-success text-white text-sm font-bold hover:opacity-90">{{ $automation->status === 'paused' ? 'Resume' : 'Switch on' }}</button>
                                                </form>
                                            @endif
                                        @endif
                                        <a href="{{ route('automations.edit', $automation->id) }}" class="px-3 py-2 rounded-lg border border-border bg-card text-sm font-semibold text-ink hover:border-accent">{{ $canEdit ? 'Edit' : 'View' }}</a>
                                        @if ($canDelete)
                                            <form method="POST" action="{{ route('automations.destroy', $automation->id) }}" onsubmit="return confirm('Delete this journey? Contacts currently in it will stop receiving its emails.');">@csrf @method('DELETE')
                                                <button type="submit" class="p-2 rounded-lg text-muted hover:text-danger hover:bg-danger-tint" title="Delete">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
