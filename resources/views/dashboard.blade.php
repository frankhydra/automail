<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">
            {{ __('Dashboard Overview') }}
        </h2>
    </x-slot>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-flash-messages />

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-card border border-border overflow-hidden shadow-sm rounded-lg p-6">
                    <div class="text-sm font-medium text-muted truncate">Total Subscribers</div>
                    <div class="mt-1 text-3xl font-bold text-ink">{{ number_format($totalContacts) }}</div>
                </div>

                <div class="bg-card border border-border overflow-hidden shadow-sm rounded-lg p-6">
                    <div class="text-sm font-medium text-muted truncate">Total Campaigns</div>
                    <div class="mt-1 text-3xl font-bold text-ink">{{ number_format($totalCampaigns) }}</div>
                </div>

                <div class="bg-card border border-border overflow-hidden shadow-sm rounded-lg p-6">
                    <div class="text-sm font-medium text-muted truncate">Total Delivered</div>
                    <div class="mt-1 text-3xl font-bold text-ink">{{ number_format($totalDelivered) }}</div>
                </div>

                <div class="bg-card border border-border overflow-hidden shadow-sm rounded-lg p-6">
                    <div class="text-sm font-medium text-muted truncate">Delivery Rate</div>
                    <div class="mt-1 text-3xl font-bold {{ $deliveryRate >= 95 ? 'text-success' : ($deliveryRate >= 80 ? 'text-warning' : 'text-danger') }}">
                        {{ $deliveryRate }}%
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Sending Volume Bar Chart -->
                <div class="bg-card border border-border shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-ink mb-4">Sending Volume (Last 7 Days)</h3>
                    <div class="relative h-64 w-full">
                        <canvas id="volumeChart"></canvas>
                    </div>
                </div>

                <!-- Engagement Line Chart -->
                <div class="bg-card border border-border shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-ink mb-4">Engagement (Last 7 Days)</h3>
                    <div class="relative h-64 w-full">
                        <canvas id="engagementChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="bg-card border border-border overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-ink mb-4">Recent Campaigns</h3>

                    @if($recentCampaigns->isEmpty())
                        <x-empty-state
                            title="No campaigns yet"
                            description="Once you create and send a campaign, its performance will show up here."
                            action-label="Create Campaign"
                            :action-href="route('campaigns.create')" />
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-border text-sm">
                                <thead class="bg-paper-tint">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-muted uppercase tracking-wider text-xs">Campaign Name</th>
                                        <th class="px-4 py-3 text-left font-semibold text-muted uppercase tracking-wider text-xs">Status</th>
                                        <th class="px-4 py-3 text-left font-semibold text-muted uppercase tracking-wider text-xs">Recipients</th>
                                        <th class="px-4 py-3 text-left font-semibold text-muted uppercase tracking-wider text-xs">Sent Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    @foreach($recentCampaigns as $campaign)
                                        <tr class="hover:bg-paper-tint/50 transition">
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <a href="{{ route('campaigns.show', $campaign->id) }}" class="text-accent hover:underline font-medium">
                                                    {{ $campaign->name }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <x-badge :status="$campaign->status" />
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-muted">
                                                {{ $campaign->sent_recipients }} / {{ $campaign->total_recipients }}
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-muted">
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Data injected from Laravel Controller
            const labels = {!! json_encode($chartDates) !!};
            const sentData = {!! json_encode($sentData) !!};
            const openData = {!! json_encode($openData) !!};
            const clickData = {!! json_encode($clickData) !!};

            // Brand palette, matched to tailwind.config.js
            const ACCENT = '#2B3A67'; // navy
            const WAX = '#8C2F39';    // oxblood
            const SUCCESS = '#3F6B4C';

            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? 'rgba(217, 208, 193, 0.1)' : 'rgba(36, 31, 27, 0.08)';
            const tickColor = isDark ? '#B3A896' : '#6B6255';

            Chart.defaults.font.family = 'Figtree, sans-serif';
            Chart.defaults.color = tickColor;

            // 1. Sending Volume Bar Chart
            const ctxVolume = document.getElementById('volumeChart').getContext('2d');
            new Chart(ctxVolume, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Emails Sent',
                        data: sentData,
                        backgroundColor: ACCENT,
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: gridColor } },
                        x: { grid: { display: false } },
                    }
                }
            });

            // 2. Engagement Line Chart (Opens & Clicks)
            const ctxEngagement = document.getElementById('engagementChart').getContext('2d');
            new Chart(ctxEngagement, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Opens',
                            data: openData,
                            backgroundColor: SUCCESS + '33',
                            borderColor: SUCCESS,
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3
                        },
                        {
                            label: 'Clicks',
                            data: clickData,
                            backgroundColor: WAX + '33',
                            borderColor: WAX,
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: gridColor } },
                        x: { grid: { display: false } },
                    }
                }
            });
        });
    </script>
</x-app-layout>
