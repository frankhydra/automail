<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-ink leading-tight">
            {{ __('Sending Identities') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <!-- Flash Status Messages -->
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

            <div class="p-6 bg-card border border-border shadow-sm rounded-xl">
                <header>
                    <h3 class="text-lg font-bold text-ink">
                        {{ __('Add Personal Sender Identity') }}
                    </h3>
                    <p class="mt-1 text-xs text-muted font-medium">
                        {{ __('Register an email address to use as your "From" address when dispatching campaigns.') }}
                    </p>
                </header>

                <form method="POST" action="{{ route('sending-identities.store') }}" class="mt-6 space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- From Name -->
                        <div>
                            <x-input-label for="from_name" :value="__('From Name')" class="text-ink font-bold" />
                            <x-text-input id="from_name" name="from_name" type="text" class="mt-1 block w-full bg-paper border-border text-ink font-medium" placeholder="e.g. Tony Support" required />
                            <x-input-error class="mt-2" :messages="$errors->get('from_name')" />
                        </div>

                        <!-- From Email -->
                        <div>
                            <x-input-label for="from_email" :value="__('From Email Address')" class="text-ink font-bold" />
                            <x-text-input id="from_email" name="from_email" type="email" class="mt-1 block w-full bg-paper border-border text-ink font-medium" placeholder="e.g. tony@gmail.com" required />
                            <x-input-error class="mt-2" :messages="$errors->get('from_email')" />
                        </div>

                        <!-- Reply-To Email -->
                        <div>
                            <x-input-label for="reply_to" :value="__('Reply-To Email (Optional)')" class="text-ink font-bold" />
                            <x-text-input id="reply_to" name="reply_to" type="email" class="mt-1 block w-full bg-paper border-border text-ink font-medium" placeholder="e.g. replies@gmail.com" />
                            <x-input-error class="mt-2" :messages="$errors->get('reply_to')" />
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <button type="submit" class="px-5 py-2.5 bg-accent text-paper font-bold text-xs uppercase tracking-wider rounded-lg shadow hover:opacity-90 transition">
                            {{ __('Register Sender Address') }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-card border border-border shadow-sm rounded-xl overflow-hidden">
                <div class="p-6 border-b border-border">
                    <h3 class="text-lg font-bold text-ink">
                        {{ __('Your Organization Senders') }}
                    </h3>
                </div>

                @if ($identities->isEmpty())
                    <p class="p-6 text-sm font-medium text-muted">No sending identities added yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border text-sm text-ink">
                            <thead class="bg-paper-tint text-xs uppercase font-bold text-muted border-b border-border">
                                <tr>
                                    <th class="px-6 py-3 text-left">From Name</th>
                                    <th class="px-6 py-3 text-left">From Email</th>
                                    <th class="px-6 py-3 text-left">Type</th>
                                    <th class="px-6 py-3 text-left">Status</th>
                                    <th class="px-6 py-3 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @foreach ($identities as $identity)
                                    <tr class="hover:bg-paper transition">
                                        <td class="px-6 py-4 font-bold text-ink">{{ $identity->from_name }}</td>
                                        <td class="px-6 py-4 text-muted font-medium">{{ $identity->from_email }}</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-paper text-ink border border-border">
                                                {{ ucfirst($identity->type) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if ($identity->isVerified())
                                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-paper-tint text-ink border border-border">
                                                    Verified
                                                </span>
                                            @else
                                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-paper text-muted border border-border">
                                                    Pending
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 flex items-center space-x-4">
                                            @if (!$identity->isVerified())
                                                <a href="{{ route('sending-identities.verify', $identity->verification_token) }}"
                                                   class="text-xs text-accent hover:underline font-bold">
                                                    Simulate Verify
                                                </a>
                                            @endif

                                            <form method="POST" action="{{ route('sending-identities.destroy', $identity->id) }}" onsubmit="return confirm('Delete this sending identity?');">
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