<x-app-layout>
    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-ink">Campaigns</h1>
                    <p class="text-sm text-muted mt-1">Create, dispatch, and review your email marketing broadcasts.</p>
                </div>
                <a href="{{ route('campaigns.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-accent text-white text-sm font-bold shadow-soft hover:opacity-90 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Launch New Campaign
                </a>
            </div>

            <x-flash-messages />

            <div class="bg-card border border-border rounded-xl shadow-soft overflow-hidden">
                @if ($campaigns->isEmpty())
                    <x-empty-state title="No campaigns yet"
                        description="Create your first email campaign to reach your subscribers."
                        action-label="Create Campaign" :action-href="route('campaigns.create')" />
                @else
                    <ul class="divide-y divide-border">
                        @foreach ($campaigns as $campaign)
                            @php
                                $hasStats = $campaign->sent_recipients > 0;
                                $openRate = $hasStats ? round($campaign->opened_recipients / $campaign->sent_recipients * 100, 1) : null;
                                $clickRate = $hasStats ? round($campaign->clicked_recipients / $campaign->sent_recipients * 100, 1) : null;
                            @endphp
                            <li class="px-6 py-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4 hover:bg-paper-tint transition">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('campaigns.show', $campaign->id) }}" class="text-lg font-extrabold text-ink hover:text-accent truncate">{{ $campaign->name }}</a>
                                        <x-badge :status="$campaign->status" />
                                    </div>
                                    <div class="text-sm text-ink mt-1 truncate"><span class="font-semibold text-muted">Subject:</span> {{ $campaign->subject }}</div>
                                    <div class="text-xs text-muted mt-1">
                                        @if ($campaign->sent_at)
                                            Sent {{ $campaign->sent_at->diffForHumans() }}
                                        @elseif ($campaign->scheduled_at)
                                            Scheduled for {{ $campaign->scheduled_at->format('M d, Y H:i') }}
                                        @else
                                            Edited {{ $campaign->updated_at->diffForHumans() }}
                                        @endif
                                        &middot; {{ number_format($campaign->recipients_count) }} recipient{{ $campaign->recipients_count === 1 ? '' : 's' }}
                                        &middot; {{ $campaign->sendingIdentity->from_name }} &lt;{{ $campaign->sendingIdentity->from_email }}&gt;
                                    </div>
                                </div>

                                <div class="flex items-center gap-6 shrink-0">
                                    @if ($hasStats)
                                        <div class="text-center">
                                            <div class="text-lg font-extrabold text-ink">{{ $openRate }}%</div>
                                            <div class="text-[10px] font-bold uppercase tracking-wider text-muted">Open rate</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-lg font-extrabold text-ink">{{ $clickRate }}%</div>
                                            <div class="text-[10px] font-bold uppercase tracking-wider text-muted">Click rate</div>
                                        </div>
                                    @endif

                                    <div class="flex items-center gap-2">
                                        <a href="{{ in_array($campaign->status, ['sent', 'sending']) ? route('analytics.index', ['campaign' => $campaign->id]) : route('campaigns.show', $campaign->id) }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-paper-tint border border-border text-sm font-semibold text-ink hover:border-accent transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                                            {{ in_array($campaign->status, ['draft']) ? 'Open' : 'Report' }}
                                        </a>
                                        <form method="POST" action="{{ route('campaigns.destroy', $campaign->id) }}" onsubmit="return confirm('Delete this campaign?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Delete campaign" class="p-2 rounded-lg text-muted hover:text-danger hover:bg-danger-tint transition">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
