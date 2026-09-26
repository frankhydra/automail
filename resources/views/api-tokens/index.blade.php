<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('API Access') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-green-100 border border-green-400 text-green-800 rounded-lg shadow-sm">{{ session('status') }}</div>
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

            @if ($plainTextToken)
                <div class="p-4 bg-yellow-50 border border-yellow-300 rounded-lg">
                    <p class="text-sm font-semibold text-yellow-800 mb-2">Copy this token now - it will not be shown again:</p>
                    <code class="block break-all bg-white p-3 rounded border border-yellow-200 text-sm">{{ $plainTextToken }}</code>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Use a token to call the AutoMail API: send it as
                    <code>Authorization: Bearer &lt;token&gt;</code> against
                    <code>/api/v1/...</code> endpoints (contacts, campaigns, templates, sending identities, analytics).
                    All data returned is scoped to your organization.
                </p>
                <form method="POST" action="{{ route('api-tokens.store') }}" class="flex flex-wrap items-end gap-4">
                    @csrf
                    <div class="flex-1 min-w-[12rem]">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Token name</label>
                        <input type="text" name="name" required placeholder="e.g. Zapier integration" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm sm:text-sm">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        Generate Token
                    </button>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($tokens as $token)
                    <div class="p-4 flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $token->name }}</p>
                            <p class="text-xs text-gray-500">
                                Created {{ $token->created_at->diffForHumans() }}
                                &middot; Last used {{ $token->last_used_at?->diffForHumans() ?? 'never' }}
                            </p>
                        </div>
                        <form method="POST" action="{{ route('api-tokens.destroy', $token->id) }}" onsubmit="return confirm('Revoke this token? Anything using it will stop working immediately.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-900">Revoke</button>
                        </form>
                    </div>
                @empty
                    <div class="p-6 text-center text-gray-500">No API tokens yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
