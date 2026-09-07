<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-extrabold text-2xl text-ink leading-tight">
                {{ __('Email Campaigns') }}
            </h2>
            <a href="{{ route('campaigns.create') }}" class="px-4 py-2 bg-accent text-paper text-xs font-bold uppercase tracking-wider rounded-lg shadow hover:opacity-90 transition">
                + Create Campaign
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <!-- Flash Messages -->
            @if (session('status'))
                <div class="p-4 bg-paper-tint border border-border text-ink rounded-lg shadow-sm font-semibold text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 bg-paper border border-wax text-wax rounded-lg shadow-sm">
                    <ul class="list-disc list-inside text-sm font-semibold">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-card border border-border shadow-sm rounded-xl overflow-hidden">
                @if ($campaigns->isEmpty())
                    <div class="text-center py-12">
                        <p class="text-muted text-sm font-medium">No campaigns created yet.</p>
                        <a href="{{ route('campaigns.create') }}" class="mt-4 inline-block text-sm font-bold text-accent hover:underline">
                            Create your first email campaign &rarr;
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border text-sm text-ink">
                            <thead class="bg-paper-tint text-xs uppercase font-bold text-muted border-b border-border">
                                <tr>
                                    <th class="px-6 py-3 text-left">Campaign Name</th>
                                    <th class="px-6 py-3 text-left">Sender</th>
                                    <th class="px-6 py-3 text-left">Target Audience</th>
                                    <th class="px-6 py-3 text-left">Status</th>
                                    <th class="px-6 py-3 text-left">Created</th>
                                    <th class="px-6 py-3 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @foreach ($campaigns as $campaign)
                                    <tr class="hover:bg-paper transition">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-ink">{{ $campaign->name }}</div>
                                            <div class="text-xs text-muted truncate max-w-xs">{{ $campaign->subject }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-medium text-muted">
                                            {{ $campaign->sendingIdentity->from_name }} ({{ $campaign->sendingIdentity->from_email }})
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-paper-tint text-ink border border-border">
                                                {{ $campaign->recipients_count }} Recipient(s)
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if ($campaign->status === 'draft')
                                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-paper text-muted border border-border">
                                                    Draft
                                                </span>
                                            @elseif ($campaign->status === 'queued')
                                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-paper-tint text-ink border border-border">
                                                    Queued
                                                </span>
                                            @elseif ($campaign->status === 'sent')
                                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-accent text-paper">
                                                    Sent
                                                </span>
                                            @else
                                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-paper text-wax border border-wax">
                                                    {{ ucfirst($campaign->status) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-xs font-medium text-muted">
                                            {{ $campaign->created_at->diffForHumans() }}
                                        </td>
                                        <td class="px-6 py-4 flex items-center space-x-4">
                                            <a href="{{ route('campaigns.show', $campaign->id) }}" class="text-xs text-accent hover:underline font-bold">
                                                View Details
                                            </a>
                                            <form method="POST" action="{{ route('campaigns.destroy', $campaign->id) }}" onsubmit="return confirm('Delete this campaign?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-wax font-bold hover:underline">
                                                    Delete
                                                </button>
                                            </form>
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