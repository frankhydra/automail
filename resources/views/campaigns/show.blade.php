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
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm mb-6">
                    <div>
                        <span class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">Sender Identity:</span>
                        <p class="font-medium text-gray-900 dark:text-gray-100 mt-1">
                            {{ $campaign->sendingIdentity->from_name }} &lt;{{ $campaign->sendingIdentity->from_email }}&gt;
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <span class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">Subject:</span>
                        <p class="font-medium text-gray-900 dark:text-gray-100 mt-1">{{ $campaign->subject }}</p>
                    </div>

                    <div>
                        <span class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">Status:</span>
                        <p class="font-semibold text-indigo-600 dark:text-indigo-400 mt-1">{{ ucfirst($campaign->status) }}</p>
                    </div>
                </div>

                <!-- Dispatch Action Bar -->
                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                    @if ($campaign->status === 'draft')
                        <form method="POST" action="{{ route('campaigns.dispatch', $campaign->id) }}" class="space-y-4 bg-gray-50 dark:bg-gray-700 p-4 rounded-md border border-gray-200 dark:border-gray-600">
                            @csrf
                            <div>
                                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Target & Dispatch Campaign</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Leave the tag filter blank to send to the whole audience chosen for this campaign. Enter comma-separated tags to narrow it to contacts that have any of them.</p>

                                <div class="flex items-center gap-6 mb-3">
                                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                        <input type="radio" name="mode" value="now" checked onchange="automailToggleSchedule(false)">
                                        Send now
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                        <input type="radio" name="mode" value="schedule" onchange="automailToggleSchedule(true)">
                                        Schedule for later
                                    </label>
                                </div>

                                <div id="schedule-fields" class="hidden mb-3">
                                    <label for="scheduled_at" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Send at (UTC) &mdash; current UTC time: <span id="current-utc-time" class="font-mono"></span>
                                    </label>
                                    <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="block w-full sm:w-64 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">AutoMail doesn't yet know your timezone, so enter this time in UTC (shown above), not your local time.</p>
                                </div>

                                <div class="flex flex-col sm:flex-row gap-4 items-end">
                                    <div class="w-full sm:w-1/2">
                                        <label for="tag_filter" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Narrow Audience by Tag (Optional)</label>
                                        <input type="text" name="tag_filter" id="tag_filter" placeholder="e.g. VIP, customer, newsletter" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                                    </div>
                                    <button type="submit" onclick="return confirm('Are you sure you want to dispatch this campaign?');" class="inline-flex items-center px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-bold text-xs uppercase tracking-widest rounded-md shadow-sm transition ease-in-out duration-150 w-full sm:w-auto justify-center">
                                        🚀 Dispatch Campaign
                                    </button>
                                </div>
                            </div>
                        </form>
                    @elseif ($campaign->status === 'scheduled')
                        <div class="p-4 bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-800 rounded-md flex flex-wrap items-center justify-between gap-4">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                Scheduled to send on <strong>{{ $campaign->scheduled_at?->format('M d, Y H:i') }} UTC</strong>
                                @if ($campaign->tag_filter)
                                    , limited to contacts tagged: <strong>{{ $campaign->tag_filter }}</strong>
                                @endif
                                .
                            </p>
                            <form method="POST" action="{{ route('campaigns.cancel', $campaign->id) }}" onsubmit="return confirm('Cancel this scheduled campaign? This cannot be undone.');">
                                @csrf
                                <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-900 dark:text-red-400">
                                    Cancel scheduled send
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="p-4 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-800 rounded-md">
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                @if ($campaign->status === 'queued' || $campaign->status === 'sending')
                                    Campaign is currently being processed by background queue workers.
                                @elseif ($campaign->status === 'sent')
                                    Campaign dispatch completed on {{ $campaign->sent_at ? $campaign->sent_at->format('M d, Y H:i') : 'N/A' }}.
                                @elseif ($campaign->status === 'failed')
                                    Campaign dispatch finished, but every delivery failed. Check the recipient table below for details.
                                @elseif ($campaign->status === 'cancelled')
                                    This campaign was cancelled before it was sent.
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recipient Breakdown Snapshot Table -->
            <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Target Recipient Snapshot</h3>
                    <span class="text-sm font-semibold bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full text-gray-600 dark:text-gray-300">
                        {{ $campaign->recipients->count() }} Recipients
                    </span>
                </div>

                @if ($campaign->recipients->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-6">No recipients targeted for this campaign.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Contact Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Email</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Opened</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Clicked</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-700 dark:text-gray-300">
                                @foreach ($campaign->recipients as $recipient)
                                    <tr>
                                        <td class="px-4 py-3 font-medium whitespace-nowrap">
                                            {{ trim(($recipient->contact->first_name ?? '') . ' ' . ($recipient->contact->last_name ?? '')) ?: '—' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">{{ $recipient->contact->email ?? $recipient->email }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($recipient->status === 'sent')
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Sent</span>
                                            @elseif ($recipient->status === 'failed')
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200" title="{{ $recipient->error_message }}">Failed</span>
                                            @elseif ($recipient->status === 'suppressed')
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Suppressed</span>
                                            @elseif ($recipient->status === 'skipped')
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300" title="{{ $recipient->error_message }}">Skipped</span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Pending</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-xs whitespace-nowrap {{ $recipient->opened_at ? 'text-indigo-600 font-semibold' : 'text-gray-400' }}">
                                            {{ $recipient->opened_at ? \Carbon\Carbon::parse($recipient->opened_at)->diffForHumans() : '--' }}
                                        </td>
                                        <td class="px-4 py-3 text-xs whitespace-nowrap {{ $recipient->clicked_at ? 'text-indigo-600 font-semibold' : 'text-gray-400' }}">
                                            {{ $recipient->clicked_at ? \Carbon\Carbon::parse($recipient->clicked_at)->diffForHumans() : '--' }}
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

    <script>
        function automailToggleSchedule(show) {
            document.getElementById('schedule-fields').classList.toggle('hidden', !show);
        }

        // Shows the current UTC time next to the "send at" field, since the form
        // asks for UTC rather than the browser's local time.
        function automailUpdateUtcClock() {
            var el = document.getElementById('current-utc-time');
            if (!el) return;
            el.textContent = new Date().toISOString().slice(0, 16).replace('T', ' ');
        }
        automailUpdateUtcClock();
        setInterval(automailUpdateUtcClock, 30000);
    </script>
</x-app-layout>