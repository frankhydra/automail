<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Sending Identities & Domains') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-green-100 border border-green-400 text-green-800 rounded-lg shadow-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 bg-red-100 border border-red-400 text-red-800 rounded-lg shadow-sm">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Add New Identity Form -->
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-1">Add New Sender</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                    Personal addresses (Gmail, Yahoo, Outlook, ...) are verified with a confirmation email.
                    Addresses on your own domain are verified through DNS records.
                </p>
                <form method="POST" action="{{ route('sending-identities.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    @csrf
                    <div>
                        <label for="from_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Name</label>
                        <input type="text" name="from_name" id="from_name" value="{{ old('from_name') }}" required placeholder="e.g. Tony Support" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="from_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Email</label>
                        <input type="email" name="from_email" id="from_email" value="{{ old('from_email') }}" required placeholder="e.g. hello@yourdomain.com" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="reply_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reply-To (Optional)</label>
                        <input type="email" name="reply_to" id="reply_to" value="{{ old('reply_to') }}" placeholder="e.g. replies@yourdomain.com" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                            Add Sender
                        </button>
                    </div>
                </form>
            </div>

            <!-- List of identities -->
            <div class="space-y-6">
                @forelse ($identities as $identity)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex flex-wrap justify-between items-center gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                    {{ $identity->from_name }} &lt;{{ $identity->from_email }}&gt;
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $identity->type === 'custom_domain' ? 'Custom domain' : 'Personal address' }}
                                    &middot; Domain: <span class="font-semibold">{{ $identity->domain }}</span>
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-4">
                                @if ($identity->verification_status === 'verified')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Verified
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        {{ $identity->type === 'custom_domain' ? 'Pending DNS verification' : 'Pending email confirmation' }}
                                    </span>

                                    @if ($identity->type === 'personal')
                                        <form method="POST" action="{{ route('sending-identities.send-verification', $identity->id) }}">
                                            @csrf
                                            <button type="submit" class="text-sm font-semibold text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">
                                                Send verification email
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('sending-identities.check-dns', $identity->id) }}">
                                            @csrf
                                            <button type="submit" class="text-sm font-semibold text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">
                                                Check DNS
                                            </button>
                                        </form>
                                    @endif

                                    @if (isset($devVerifyLinks[$identity->id]))
                                        <a href="{{ $devVerifyLinks[$identity->id] }}" class="text-xs font-semibold text-gray-500 hover:text-gray-700 underline" title="Only shown when APP_ENV=local">
                                            Dev only: verify now
                                        </a>
                                    @endif
                                @endif

                                <form method="POST" action="{{ route('sending-identities.destroy', $identity->id) }}" onsubmit="return confirm('Delete this sending identity?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-900 dark:text-red-400">Delete</button>
                                </form>
                            </div>
                        </div>

                        <!-- DNS instructions for custom domains -->
                        @if ($identity->type === 'custom_domain' && $identity->verification_status !== 'verified' && isset($records[$identity->id]))
                            <div class="bg-gray-50 dark:bg-gray-900 p-6">
                                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Configure your DNS records</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">
                                    At your domain registrar or DNS host for <strong>{{ $identity->domain }}</strong>, add the records below,
                                    then click <strong>Check DNS</strong>. All four must be found before this sender is verified.
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-500 mb-4 italic">
                                    DNS changes can take anywhere from a few minutes up to 48 hours to propagate. If "Check DNS" doesn't
                                    find a record right away, wait a while and try again.
                                </p>

                                <div class="space-y-4">
                                    @foreach ($records[$identity->id] as $index => $record)
                                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md p-4">
                                            <div class="flex justify-between items-center mb-2">
                                                <span class="text-xs font-bold text-gray-500 uppercase">{{ $index + 1 }}. {{ $record['label'] }}</span>
                                                @if ($record['key'] !== 'ownership')
                                                    <span class="text-xs {{ $record['status'] === 'verified' ? 'text-green-600' : 'text-yellow-600' }} font-semibold">{{ ucfirst($record['status']) }}</span>
                                                @endif
                                            </div>
                                            <div class="grid grid-cols-12 gap-2 text-sm font-mono text-gray-800 dark:text-gray-200">
                                                <div class="col-span-3 text-gray-500 text-xs">Type</div>
                                                <div class="col-span-3 text-gray-500 text-xs">Host / Name</div>
                                                <div class="col-span-6 text-gray-500 text-xs">Value</div>

                                                <div class="col-span-3 break-words">{{ $record['type'] }}</div>
                                                <div class="col-span-3 break-all">{{ $record['host'] }}</div>
                                                <div class="col-span-6 flex items-start gap-2">
                                                    <span class="break-all bg-gray-100 dark:bg-gray-700 p-1 rounded flex-1" id="dns-value-{{ $identity->id }}-{{ $record['key'] }}">{{ $record['value'] }}</span>
                                                    <button type="button"
                                                        onclick="automailCopyDnsValue(this, 'dns-value-{{ $identity->id }}-{{ $record['key'] }}')"
                                                        class="shrink-0 text-xs font-semibold text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 px-2 py-1">
                                                        Copy
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg text-center text-gray-500">
                        No sending identities configured. Add one above to get started.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
<script>
        // Copies a DNS record value to the clipboard for the "Copy" button next to each record.
        function automailCopyDnsValue(button, elementId) {
            var text = document.getElementById(elementId).innerText;
            var originalLabel = button.innerText;

            navigator.clipboard.writeText(text).then(function () {
                button.innerText = 'Copied!';
                setTimeout(function () { button.innerText = originalLabel; }, 1500);
            });
        }
    </script>
</x-app-layout>
