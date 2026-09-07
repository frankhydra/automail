<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-ink leading-tight">
            {{ __('Organization Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

                <!-- Subscribed Contacts -->
                <div class="p-6 bg-card border border-border shadow-sm rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-muted">Subscribed Contacts</p>
                            <p class="text-3xl font-extrabold text-ink mt-2">{{ number_format($totalContacts) }}</p>
                        </div>
                        <div class="p-3 bg-paper-tint rounded-full text-accent">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                    <a href="{{ route('contacts.import.show') }}" class="mt-4 block text-xs font-bold text-accent hover:underline">
                        + Import Contacts &rarr;
                    </a>
                </div>

                <!-- Total Campaigns -->
                <div class="p-6 bg-card border border-border shadow-sm rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-muted">Total Campaigns</p>
                            <p class="text-3xl font-extrabold text-ink mt-2">{{ number_format($totalCampaigns) }}</p>
                        </div>
                        <div class="p-3 bg-paper-tint rounded-full text-accent">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <a href="{{ route('campaigns.index') }}" class="mt-4 block text-xs font-bold text-accent hover:underline">
                        View Campaigns &rarr;
                    </a>
                </div>

                <!-- Emails Delivered -->
                <div class="p-6 bg-card border border-border shadow-sm rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-muted">Emails Delivered</p>
                            <p class="text-3xl font-extrabold text-ink mt-2">{{ number_format($totalDelivered) }}</p>
                        </div>
                        <div class="p-3 bg-paper-tint rounded-full text-accent">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <span class="mt-4 block text-xs text-muted font-medium">Processed by queue workers</span>
                </div>

                <!-- Delivery Success -->
                <div class="p-6 bg-card border border-border shadow-sm rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-muted">Delivery Success</p>
                            <p class="text-3xl font-extrabold text-ink mt-2">{{ $deliveryRate }}%</p>
                        </div>
                        <div class="p-3 bg-paper-tint rounded-full text-accent">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                    </div>
                    <span class="mt-4 block text-xs text-muted font-medium">Successful vs Failed dispatches</span>
                </div>

            </div>

            <div class="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-border flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold text-ink">Recent Campaigns</h3>
                        <p class="text-xs text-muted">Performance summary for recent email dispatches.</p>
                    </div>
                    <a href="{{ route('campaigns.create') }}" class="inline-flex items-center px-4 py-2 bg-accent text-paper text-xs font-bold uppercase tracking-wider rounded-lg shadow hover:opacity-90 transition">
                        + New Campaign
                    </a>
                </div>

                @if ($recentCampaigns->isEmpty())
                    <div class="text-center py-12">
                        <p class="text-sm font-medium text-muted">No campaigns created yet.</p>
                        <a href="{{ route('campaigns.create') }}" class="mt-3 inline-block text-xs font-bold text-accent hover:underline">
                            Create your first email campaign &rarr;
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border text-sm text-ink">
                            <thead class="bg-paper-tint text-xs uppercase font-bold text-muted border-b border-border">
                                <tr>
                                    <th class="px-6 py-3 text-left">Campaign</th>
                                    <th class="px-6 py-3 text-left">Sender</th>
                                    <th class="px-6 py-3 text-left">Status</th>
                                    <th class="px-6 py-3 text-left">Progress</th>
                                    <th class="px-6 py-3 text-left">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @foreach ($recentCampaigns as $campaign)
                                    <tr class="hover:bg-paper transition">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-ink">{{ $campaign->name }}</div>
                                            <div class="text-xs text-muted truncate max-w-xs">{{ $campaign->subject }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-medium text-muted">
                                            {{ $campaign->sendingIdentity->from_name ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            @if ($campaign->status === 'sent')
                                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-paper-tint text-ink border border-border">Sent</span>
                                            @elseif ($campaign->status === 'queued' || $campaign->status === 'sending')
                                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-accent text-paper">Processing</span>
                                            @else
                                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-paper text-muted border border-border">Draft</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-xs font-bold text-ink">
                                            {{ $campaign->sent_recipients }} / {{ $campaign->total_recipients }} Delivered
                                        </td>
                                        <td class="px-6 py-4">
                                            <a href="{{ route('campaigns.show', $campaign->id) }}" class="text-xs font-bold text-accent hover:underline">
                                                View &rarr;
                                            </a>
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
</x-app-layout>