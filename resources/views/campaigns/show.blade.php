<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Campaign Details: ') }} {{ $campaign->name }}
            </h2>
            <a href="{{ route('campaigns.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline font-semibold">
                &larr; Back to Campaigns
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Status Messages -->
            @if (session('status'))
                <div class="p-4 bg-green-100 border border-green-400 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-lg shadow-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 bg-red-100 border border-red-400 text-red-800 dark:bg-red-900 dark:text-red-200 rounded-lg shadow-sm">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Campaign Summary Overview Card -->
            <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">Sender Identity:</span>
                        <p class="font-medium text-gray-900 dark:text-gray-100 mt-1">
                            {{ $campaign->sendingIdentity->from_name }} &lt;{{ $campaign->sendingIdentity->from_email }}&gt;
                        </p>
                    </div>

                    <div>
                        <span class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">Subject:</span>
                        <p class="font-medium text-gray-900 dark:text-gray-100 mt-1">{{ $campaign->subject }}</p>
                    </div>

                    <div>
                        <span class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">Status:</span>
                        <p class="font-semibold text-indigo-600 dark:text-indigo-400 mt-1">{{ ucfirst($campaign->status) }}</p>
                    </div>

                    <div>
                        <span class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">Target Audience Count:</span>
                        <p class="font-semibold text-gray-900 dark:text-gray-100 mt-1">{{ $campaign->recipients->count() }} Recipients</p>
                    </div>
                </div>

                <!-- Dispatch Action Bar -->
                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            @if ($campaign->status === 'draft')
                                Ready to dispatch? Clicking dispatch will queue emails for all {{ $campaign->recipients->count() }} target recipients.
                            @elseif ($campaign->status === 'queued' || $campaign->status === 'sending')
                                Campaign is currently being processed by background queue workers.
                            @elseif ($campaign->status === 'sent')
                                Campaign dispatch completed on {{ $campaign->sent_at ? $campaign->sent_at->format('M d, Y H:i') : 'N/A' }}.
                            @endif
                        </p>
                    </div>

                    @if ($campaign->status === 'draft')
                        <form method="POST" action="{{ route('campaigns.dispatch', $campaign->id) }}" onsubmit="return confirm('Are you sure you want to dispatch this campaign now?');">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-green-600 hover:bg-green-700 focus:bg-green-700 active:bg-green-800 text-white font-bold text-xs uppercase tracking-widest rounded-md shadow-sm transition ease-in-out duration-150">
                                🚀 Dispatch Campaign Now
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Recipient Breakdown Snapshot Table -->
            <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Target Recipient Snapshot</h3>

                @if ($campaign->recipients->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">No recipients snapshotted for this campaign.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Contact Name</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Email</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Dispatch Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Sent At</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-700 dark:text-gray-300">
                                @foreach ($campaign->recipients as $recipient)
                                    <tr>
                                        <td class="px-4 py-2 font-medium">
                                            {{ trim(($recipient->contact->first_name ?? '') . ' ' . ($recipient->contact->last_name ?? '')) ?: '—' }}
                                        </td>
                                        <td class="px-4 py-2">{{ $recipient->contact->email }}</td>
                                        <td class="px-4 py-2">
                                            @if ($recipient->status === 'sent')
                                                <span class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    Sent
                                                </span>
                                            @elseif ($recipient->status === 'failed')
                                                <span class="px-2 py-1 text-xs font-semibold rounded bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                    Failed
                                                </span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-semibold rounded bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                    Pending
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-xs text-gray-400">
                                            {{ $recipient->sent_at ? $recipient->sent_at->format('Y-m-d H:i:s') : 'Pending Worker Dispatch' }}
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