@php
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $progress = $audienceTarget > 0 ? min(100, (int) round($subscribedContacts / $audienceTarget * 100)) : 0;

    // Rule-based suggestions computed from real data (no AI service involved yet).
    $tips = [];
    if ($bouncedContacts > 0) {
        $tips[] = ['tone' => 'danger', 'title' => 'Clean your list', 'text' => "{$bouncedContacts} contact(s) bounced. Review them in Audience to protect your sender reputation.", 'href' => route('contacts.index'), 'cta' => 'Open Audience'];
    }
    if ($draftCampaigns > 0) {
        $tips[] = ['tone' => 'warning', 'title' => 'Unfinished work', 'text' => "You have {$draftCampaigns} draft campaign(s) waiting to be sent.", 'href' => route('campaigns.index'), 'cta' => 'Review drafts'];
    }
    if ($segmentCount === 0 && $subscribedContacts > 0) {
        $tips[] = ['tone' => 'info', 'title' => 'Target better', 'text' => 'Create a segment to send campaigns to the people most likely to engage.', 'href' => route('segments.create'), 'cta' => 'Create segment'];
    }
    if ($totalDelivered > 0 && $openRate < 20) {
        $tips[] = ['tone' => 'warning', 'title' => 'Boost your opens', 'text' => 'Your open rate is under 20%. Try a shorter, more specific subject line.', 'href' => route('campaigns.create'), 'cta' => 'New campaign'];
    }
    if (empty($tips)) {
        $tips[] = ['tone' => 'success', 'title' => 'All clear', 'text' => 'Nothing needs your attention right now.', 'href' => null, 'cta' => null];
    }
    $toneClasses = [
        'danger' => 'bg-danger-tint border-danger/30',
        'warning' => 'bg-warning-tint border-warning/30',
        'info' => 'bg-paper-tint border-border',
        'success' => 'bg-success-tint border-success/30',
    ];
@endphp

<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <x-flash-messages />

            <!-- Hero -->
            <div class="rounded-2xl bg-accent text-[#FFF3E4] p-6 sm:p-8 shadow-soft relative overflow-hidden">
                <div class="absolute -right-16 -top-16 w-64 h-64 rounded-full bg-sun opacity-25"></div>
                <div class="absolute -right-4 -bottom-24 w-56 h-56 rounded-full bg-[#1D1E22] opacity-20"></div>
                <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-[#F6D2A1]">Audience growth engine</div>
                        <h1 class="mt-1 text-2xl sm:text-3xl font-extrabold tracking-tight">{{ $greeting }}, {{ $organizationName }}</h1>
                        <p class="mt-2 text-sm text-[#FFF3E4]/85 max-w-xl">
                            @if ($scheduledCampaigns > 0)
                                You have {{ $scheduledCampaigns }} campaign{{ $scheduledCampaigns === 1 ? '' : 's' }} scheduled.
                            @else
                                No campaigns are scheduled right now.
                            @endif
                            You have {{ number_format($subscribedContacts) }} subscribed contact{{ $subscribedContacts === 1 ? '' : 's' }}.
                        </p>
                    </div>
                    <a href="{{ route('templates.create') }}" class="shrink-0 inline-flex items-center justify-center px-5 py-3 rounded-lg bg-[#1D1E22] text-[#FFF3E4] text-sm font-bold hover:bg-black transition">
                        Design New Email
                    </a>
                </div>
            </div>

            <!-- Stat cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-card border border-border rounded-xl p-5 shadow-soft">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-muted">Total audience</div>
                    <div class="mt-2 text-3xl font-extrabold text-ink">{{ number_format($totalContacts) }}</div>
                    <div class="mt-2 text-xs font-semibold {{ $newContactsThisMonth > 0 ? 'text-success' : 'text-muted' }}">
                        {{ $newContactsThisMonth > 0 ? '+'.number_format($newContactsThisMonth).' this month' : 'No new contacts this month' }}
                    </div>
                </div>

                <div class="bg-card border border-border rounded-xl p-5 shadow-soft">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-muted">Avg. open rate</div>
                    <div class="mt-2 text-3xl font-extrabold text-ink">{{ $openRate }}%</div>
                    <div class="mt-2 text-xs text-muted">{{ number_format($totalOpened) }} of {{ number_format($totalDelivered) }} delivered opened</div>
                </div>

                <div class="bg-card border border-border rounded-xl p-5 shadow-soft">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-muted">Click-through rate</div>
                    <div class="mt-2 text-3xl font-extrabold text-ink">{{ $clickRate }}%</div>
                    <div class="mt-2 text-xs text-muted">{{ number_format($totalClicked) }} unique click{{ $totalClicked === 1 ? '' : 's' }}</div>
                </div>

                <div class="bg-card border border-border rounded-xl p-5 shadow-soft">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-muted">Subscribed rate</div>
                    <div class="mt-2 text-3xl font-extrabold text-ink">{{ $subscribedRate }}%</div>
                    <div class="mt-2 text-xs text-muted">{{ number_format($subscribedContacts) }} active subscriber{{ $subscribedContacts === 1 ? '' : 's' }}</div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bg-card border border-border rounded-xl p-6 shadow-soft">
                    <h3 class="text-base font-bold text-ink mb-4">Sending volume <span class="text-muted font-medium text-sm">(last 7 days)</span></h3>
                    <div class="relative h-60 w-full"><canvas id="volumeChart"></canvas></div>
                </div>
                <div class="bg-card border border-border rounded-xl p-6 shadow-soft">
                    <h3 class="text-base font-bold text-ink mb-4">Engagement <span class="text-muted font-medium text-sm">(last 7 days)</span></h3>
                    <div class="relative h-60 w-full"><canvas id="engagementChart"></canvas></div>
                </div>
            </div>

            <!-- Recent campaigns + insights -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 bg-card border border-border rounded-xl shadow-soft overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-border">
                        <h3 class="text-base font-bold text-ink">Recent campaigns</h3>
                        <a href="{{ route('campaigns.index') }}" class="text-sm font-semibold text-accent hover:underline">View all &rarr;</a>
                    </div>

                    @if ($recentCampaigns->isEmpty())
                        <x-empty-state title="No campaigns yet"
                            description="Once you create and send a campaign, its performance will show up here."
                            action-label="Create Campaign" :action-href="route('campaigns.create')" />
                    @else
                        <ul class="divide-y divide-border">
                            @foreach ($recentCampaigns as $campaign)
                                @php
                                    $rate = $campaign->sent_recipients > 0 ? round($campaign->opened_recipients / $campaign->sent_recipients * 100, 1) : null;
                                @endphp
                                <li>
                                    <a href="{{ route('campaigns.show', $campaign->id) }}" class="flex items-center justify-between gap-4 px-6 py-4 hover:bg-paper-tint transition">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-ink truncate">{{ $campaign->name }}</span>
                                                <x-badge :status="$campaign->status" />
                                            </div>
                                            <div class="text-sm text-muted truncate">{{ $campaign->subject }}</div>
                                            <div class="text-xs text-muted mt-0.5">
                                                {{ $campaign->sent_at ? 'Sent '.$campaign->sent_at->diffForHumans() : ($campaign->scheduled_at ? 'Scheduled for '.$campaign->scheduled_at->format('M d, H:i') : 'Created '.$campaign->created_at->diffForHumans()) }}
                                                &middot; {{ number_format($campaign->total_recipients) }} recipients
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            @if ($rate !== null)
                                                <div class="text-lg font-extrabold text-ink">{{ $rate }}%</div>
                                                <div class="text-[10px] font-bold uppercase tracking-wider text-muted">Opens</div>
                                            @else
                                                <div class="text-xs italic text-muted">No data yet</div>
                                            @endif
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="bg-card border border-border rounded-xl shadow-soft p-6 flex flex-col">
                    <h3 class="text-base font-bold text-ink mb-4">Insights</h3>

                    <div class="space-y-3 flex-1">
                        @foreach ($tips as $tip)
                            <div class="rounded-lg border p-4 {{ $toneClasses[$tip['tone']] }}">
                                <div class="text-sm font-bold text-ink">{{ $tip['title'] }}</div>
                                <p class="text-xs text-ink mt-1">{{ $tip['text'] }}</p>
                                @if ($tip['href'])
                                    <a href="{{ $tip['href'] }}" class="inline-block mt-2 text-xs font-bold text-ink underline">{{ $tip['cta'] }} &rarr;</a>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 pt-4 border-t border-border">
                        <div class="flex items-center justify-between text-xs font-semibold text-muted mb-2">
                            <span>Audience growth target</span>
                            <span class="text-ink">{{ number_format($subscribedContacts) }} / {{ number_format($audienceTarget) }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-paper-tint overflow-hidden">
                            <div class="h-full rounded-full bg-sun" style="width: {{ $progress }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Read by resources/js/dashboard-charts.js (bundled by Vite). --}}
    <script type="application/json" id="dashboard-chart-data">
        {!! json_encode(['labels' => $chartDates, 'sent' => $sentData, 'opens' => $openData, 'clicks' => $clickData]) !!}
    </script>
</x-app-layout>
