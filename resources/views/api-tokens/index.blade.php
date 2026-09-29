<x-settings-layout active="api">
    @if ($plainTextToken)
        <div class="p-4 bg-warning-tint border border-warning/30 rounded-lg">
            <p class="text-sm font-semibold text-warning mb-2">Copy this token now - it will not be shown again:</p>
            <code class="block break-all bg-card p-3 rounded border border-warning/30 text-sm text-ink">{{ $plainTextToken }}</code>
        </div>
    @endif

    <div class="bg-card border border-border shadow-sm rounded-lg p-6">
        <p class="text-sm text-muted mb-4">
            Use a token to call the AutoMail API: send it as
            <code>Authorization: Bearer &lt;token&gt;</code> against
            <code>/api/v1/...</code> endpoints (contacts, campaigns, templates, sending identities, analytics).
            All data returned is scoped to your organization.
        </p>
        <form method="POST" action="{{ route('api-tokens.store') }}" class="flex flex-wrap items-end gap-4">
            @csrf
            <div class="flex-1 min-w-[12rem]">
                <label class="block text-sm font-medium text-ink">Token name</label>
                <input type="text" name="name" required placeholder="e.g. Zapier integration" class="mt-1 block w-full rounded-md border-border bg-paper text-ink shadow-sm sm:text-sm focus:border-accent focus:ring-accent">
            </div>
            <button type="submit" class="px-4 py-2 bg-accent border border-transparent rounded-md font-semibold text-xs text-paper uppercase tracking-widest hover:opacity-90">
                Generate Token
            </button>
        </form>
    </div>

    <div class="bg-card border border-border shadow-sm rounded-lg divide-y divide-border">
        @forelse ($tokens as $token)
            <div class="p-4 flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-ink">{{ $token->name }}</p>
                    <p class="text-xs text-muted">
                        Created {{ $token->created_at->diffForHumans() }}
                        &middot; Last used {{ $token->last_used_at?->diffForHumans() ?? 'never' }}
                    </p>
                </div>
                <form method="POST" action="{{ route('api-tokens.destroy', $token->id) }}" onsubmit="return confirm('Revoke this token? Anything using it will stop working immediately.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-semibold text-danger hover:underline">Revoke</button>
                </form>
            </div>
        @empty
            <x-empty-state title="No API tokens yet" description="Generate one above to start calling the AutoMail API." />
        @endforelse
    </div>
</x-settings-layout>
