<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">
            {{ __('Dashboard Overview') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-flash-messages />

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-card border border-border shadow-sm rounded-xl p-6 flex items-start gap-4">
                    <div class="w-10 h-10 rounded-lg bg-info-tint flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-muted truncate">Total Subscribers</div>
                        <div class="mt-0.5 text-2xl font-bold text-ink">{{ number_format($totalContacts) }}</div>
                    </div>
                </div>

                <div class="bg-card border border-border shadow-sm rounded-xl p-6 flex items-start gap-4">
                    <div class="w-10 h-10 rounded-lg bg-warning-tint flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.769 59.769 0 0121.485 12 59.768 59.768 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-muted truncate">Total Campaigns</div>
                        <div class="mt-0.5 text-2xl font-bold text-ink">{{ number_format($totalCampaigns) }}</div>
                    </div>
                </div>

                <div class="bg-card border border-border shadow-sm rounded-xl p-6 flex items-start gap-4">
                    <div class="w-10 h-10 rounded-lg bg-success-tint flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-muted truncate">Total Delivered</div>
                        <div class="mt-0.5 text-2xl font-bold text-ink">{{ number_format($totalDelivered) }}</div>
                    </div>
                </div>

                <div class="bg-card border border-border shadow-sm rounded-xl p-6 flex items-start gap-4">
                    <div class="w-10 h-10 rounded-lg {{ $deliveryRate >= 95 ? 'bg-success-tint' : ($deliveryRate >= 80 ? 'bg-warning-tint' : 'bg-danger-tint') }} flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 {{ $deliveryRate >= 95 ? 'text-success' : ($deliveryRate >= 80 ? 'text-warning' : 'text-danger') }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-muted truncate">Delivery Rate</div>
                        <div class="mt-0.5 text-2xl font-bold {{ $deliveryRate >= 95 ? 'text-success' : ($deliveryRate >= 80 ? 'text-warning' : 'text-danger') }}">
                            {{ $deliveryRate }}%
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-card border border-border shadow-sm rounded-xl p-6">
                    <h3 class="text-base font-semibold text-ink mb-4">Sending Volume (Last 7 Days)</h3>
                    <div class="relative h-64 w-full">
                        <canvas id="volumeChart"></canvas>
                    </div>
                </div>

                <div class="bg-card border border-border shadow-sm rounded-xl p-6">
                    <h3 class="text-base font-semibold text-ink mb-4">Engagement (Last 7 Days)</h3>
                    <div class="relative h-64 w-full">
                        <canvas id="engagementChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="bg-card border border-border shadow-sm rounded-xl overflow-hidden">
                <div class="p-6">
                    <h3 class="text-base font-semibold text-ink mb-4">Recent Campaigns</h3>

                    @if($recentCampaigns->isEmpty())
                        <x-empty-state
                            title="No campaigns yet"
                            description="Once you create and send a campaign, its performance will show up here."
                            action-label="Create Campaign"
                            :action-href="route('campaigns.create')" />
                    @else
                        <div class="overflow-x-auto -mx-6">
                            <table class="min-w-full divide-y divide-border text-sm">
                                <thead class="bg-paper-tint">
                                    <tr>
                                        <th class="px-6 py-3 text-left font-semibold text-muted uppercase tracking-wider text-xs">Campaign Name</th>
                                        <th class="px-6 py-3 text-left font-semibold text-muted uppercase tracking-wider text-xs">Status</th>
                                        <th class="px-6 py-3 text-left font-semibold text-muted uppercase tracking-wider text-xs">Recipients</th>
                                        <th class="px-6 py-3 text-left font-semibold text-muted uppercase tracking-wider text-xs">Sent Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    @foreach($recentCampaigns as $campaign)
                                        <tr class="hover:bg-paper-tint/50 transition">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="{{ route('campaigns.show', $campaign->id) }}" class="text-accent hover:underline font-medium">
                                                    {{ $campaign->name }}
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <x-badge :status="$campaign->status" />
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-muted">
                                                {{ $campaign->sent_recipients }} / {{ $campaign->total_recipients }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-muted">
                                                {{ $campaign->sent_at ? $campaign->sent_at->format('M d, Y H:i') : 'N/A' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Read by resources/js/dashboard-charts.js (bundled by Vite, not a CDN <script>
         tag - see the milestone notes for why the old version loaded slowly/blank). --}}
    <script type="application/json" id="dashboard-chart-data">
        {!! json_encode(['labels' => $chartDates, 'sent' => $sentData, 'opens' => $openData, 'clicks' => $clickData]) !!}
    </script>
</x-app-layout>
