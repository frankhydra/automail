<x-app-layout>
    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-ink">Campaign Analytics</h1>
                    <p class="text-sm text-muted mt-1">
                        @if ($campaign) Live reporting for <span class="font-semibold text-ink">{{ $campaign->name }}</span> @else Performance reports for your sent campaigns. @endif
                    </p>
                </div>

                @if ($campaigns->count() > 1)
                    <form method="GET" action="{{ route('analytics.index') }}">
                        <select name="campaign" onchange="this.form.submit()" class="text-sm rounded-lg bg-card border-border text-ink focus:border-accent focus:ring-accent max-w-xs">
                            @foreach ($campaigns as $option)
                                <option value="{{ $option->id }}" @selected($campaign && $campaign->id === $option->id)>{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>

            @if (!$campaign)
                <div class="bg-card border border-border shadow-soft rounded-xl">
                    <x-empty-state title="No sent campaigns yet"
                        description="Once a campaign has been sent, its delivery, opens, clicks and top links will appear here."
                        action-label="Create Campaign" :action-href="route('campaigns.create')" />
                </div>
            @else
                <!-- Stat cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-card border border-border rounded-xl p-5 shadow-soft">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-muted">Total sent</div>
                        <div class="mt-2 text-3xl font-extrabold text-ink">{{ number_format($sent) }}</div>
                        <div class="mt-2 text-xs font-semibold text-success">{{ $deliveryRate }}% delivery rate</div>
                    </div>
                    <div class="bg-card border border-border rounded-xl p-5 shadow-soft">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-muted">Unique opens</div>
                        <div class="mt-2 text-3xl font-extrabold text-ink">{{ number_format($opened) }}</div>
                        <div class="mt-2 text-xs font-semibold text-success">{{ $openRate }}% open rate</div>
                    </div>
                    <div class="bg-card border border-border rounded-xl p-5 shadow-soft">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-muted">Unique clicks</div>
                        <div class="mt-2 text-3xl font-extrabold text-ink">{{ number_format($clicked) }}</div>
                        <div class="mt-2 text-xs font-semibold text-success">{{ $clickToOpen }}% click-to-open</div>
                    </div>
                    <div class="bg-card border border-border rounded-xl p-5 shadow-soft">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-muted">Unsubscribed</div>
                        <div class="mt-2 text-3xl font-extrabold text-ink">{{ number_format($unsubscribed) }}</div>
                        <div class="mt-2 text-xs text-muted">{{ $unsubscribeRate }}% &middot; {{ $bounced }} bounced &middot; {{ $complained }} complaints</div>
                    </div>
                </div>

                <!-- Hourly chart -->
                <div class="bg-card border border-border rounded-xl p-6 shadow-soft">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-ink">Hourly engagement <span class="text-muted font-medium text-sm">(first 24 hours after sending)</span></h3>
                        <span class="text-xs text-muted">Timezone: {{ $timezone }}</span>
                    </div>
                    @if (array_sum($chart['opens']) + array_sum($chart['clicks']) === 0)
                        <p class="py-12 text-center text-sm text-muted">No opens or clicks recorded in the first 24 hours yet.</p>
                    @else
                        <div class="relative h-64 w-full"><canvas id="hourlyChart"></canvas></div>
                    @endif
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- Mail apps -->
                    <div class="bg-card border border-border rounded-xl p-6 shadow-soft">
                        <h3 class="text-base font-bold text-ink mb-4">Mail app split</h3>
                        @forelse ($clients as $client)
                            <div class="mb-4 last:mb-0">
                                <div class="flex items-center justify-between text-sm mb-1.5">
                                    <span class="font-semibold text-ink">{{ $client['name'] }}</span>
                                    <span class="font-bold text-ink">{{ $client['percent'] }}%</span>
                                </div>
                                <div class="h-2 rounded-full bg-paper-tint overflow-hidden"><div class="h-full rounded-full bg-accent" style="width: {{ $client['percent'] }}%"></div></div>
                            </div>
                        @empty
                            <p class="text-sm text-muted">No opens recorded yet.</p>
                        @endforelse
                        <p class="text-[11px] text-muted mt-4">Best-effort estimate from how images were loaded. Privacy features in some mail apps hide the real app, so treat this as a guide. Opens recorded before this report existed show as Unknown.</p>
                    </div>

                    <!-- Top links -->
                    <div class="bg-card border border-border rounded-xl p-6 shadow-soft">
                        <h3 class="text-base font-bold text-ink mb-4">Top performing links</h3>
                        @forelse ($topLinks as $link)
                            <div class="flex items-center justify-between gap-3 mb-2 last:mb-0 bg-paper-tint border border-border rounded-lg px-3 py-2">
                                <span class="font-mono text-xs text-ink truncate" title="{{ $link->url }}">{{ $link->url }}</span>
                                <span class="text-xs font-bold text-ink whitespace-nowrap">{{ number_format($link->clicks) }} click{{ $link->clicks == 1 ? '' : 's' }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-muted">No link clicks recorded yet. Clicks are counted from now on.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if ($campaign)
        <script type="application/json" id="analytics-chart-data">
            {!! json_encode($chart) !!}
        </script>
    @endif
</x-app-layout>
