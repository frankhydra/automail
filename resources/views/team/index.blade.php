<x-settings-layout active="team">
    <!-- Invite form -->
    <div class="bg-card border border-border shadow-sm rounded-lg p-6">
        <h3 class="text-lg font-medium text-ink mb-4">Invite a Teammate</h3>
        <form method="POST" action="{{ route('team.invite') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            @csrf
            <div>
                <label class="block text-sm font-medium text-ink">Email</label>
                <input type="email" name="email" required class="mt-1 block w-full rounded-md border-border bg-paper text-ink shadow-sm sm:text-sm focus:border-accent focus:ring-accent">
            </div>
            <div>
                <label class="block text-sm font-medium text-ink">Role</label>
                <select name="role" class="mt-1 block w-full rounded-md border-border bg-paper text-ink shadow-sm sm:text-sm focus:border-accent focus:ring-accent">
                    @foreach ($roles as $role)
                        <option value="{{ $role }}">{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
           </div>
            <button type="submit" class="inline-flex justify-center items-center px-4 py-2 bg-accent border border-transparent rounded-md font-semibold text-xs text-paper uppercase tracking-widest hover:opacity-90">
                Send Invite
            </button>
        </form>
    </div>

    <!-- Pending invitations -->
    @if ($invitations->isNotEmpty())
        <div class="bg-card border border-border shadow-sm rounded-lg divide-y divide-border">
            <div class="p-4 text-sm font-semibold text-ink">Pending Invitations</div>
            @foreach ($invitations as $invitation)
                <div class="p-4 flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm text-ink">{{ $invitation->email }}</p>
                        <p class="text-xs text-muted">{{ ucfirst($invitation->role) }} &middot; invited {{ $invitation->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <form method="POST" action="{{ route('team.invitations.resend', $invitation->id) }}">
                            @csrf
                            <button type="submit" class="text-sm font-semibold text-accent hover:underline">Resend</button>
                        </form>
                        <form method="POST" action="{{ route('team.invitations.cancel', $invitation->id) }}" onsubmit="return confirm('Cancel this invitation?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-semibold text-danger hover:underline">Cancel</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Members -->
    <div class="bg-card border border-border shadow-sm rounded-lg divide-y divide-border">
        <div class="p-4 text-sm font-semibold text-ink">Members</div>
        @foreach ($members as $member)
            <div class="p-4 flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-ink">
                        {{ $member->name }} @if($member->id === $currentUserId) <span class="text-xs text-muted">(you)</span> @endif
                    </p>
                    <p class="text-xs text-muted">{{ $member->email }}</p>
                </div>
                <div class="flex items-center gap-3">
                    @if ($member->pivot->role === 'owner')
                        <span class="text-xs font-semibold px-2 py-1 rounded-full bg-paper-tint text-ink">Owner</span>
                    @else
                        <form method="POST" action="{{ route('team.members.role', $member->id) }}" class="flex items-center gap-2">
                            @csrf
                            @method('PATCH')
                            <select name="role" onchange="this.form.submit()" class="text-xs rounded-md border-border bg-paper text-ink">
                                @foreach ($roles as $role)
                                    <option value="{{ $role }}" @selected($member->pivot->role === $role)>{{ ucfirst($role) }}</option>
                                @endforeach
                            </select>
                        </form>
                        <form method="POST" action="{{ route('team.members.remove', $member->id) }}" onsubmit="return confirm('Remove {{ $member->name }} from this organization?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-semibold text-danger hover:underline">Remove</button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-settings-layout>
