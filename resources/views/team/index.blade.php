<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Team') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

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

            <!-- Invite form -->
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Invite a Teammate</h3>
                <form method="POST" action="{{ route('team.invite') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                        <input type="email" name="email" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
                        <select name="role" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm sm:text-sm">
                            @foreach ($roles as $role)
                                <option value="{{ $role }}">{{ ucfirst($role) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        Send Invite
                    </button>
                </form>
            </div>

            <!-- Pending invitations -->
            @if ($invitations->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg divide-y divide-gray-200 dark:divide-gray-700">
                    <div class="p-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Pending Invitations</div>
                    @foreach ($invitations as $invitation)
                        <div class="p-4 flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm text-gray-800 dark:text-gray-200">{{ $invitation->email }}</p>
                                <p class="text-xs text-gray-500">{{ ucfirst($invitation->role) }} &middot; invited {{ $invitation->created_at->diffForHumans() }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <form method="POST" action="{{ route('team.invitations.resend', $invitation->id) }}">
                                    @csrf
                                    <button type="submit" class="text-sm font-semibold text-indigo-600 hover:text-indigo-900">Resend</button>
                                </form>
                                <form method="POST" action="{{ route('team.invitations.cancel', $invitation->id) }}" onsubmit="return confirm('Cancel this invitation?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-900">Cancel</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Members -->
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg divide-y divide-gray-200 dark:divide-gray-700">
                <div class="p-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Members</div>
                @foreach ($members as $member)
                    <div class="p-4 flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                {{ $member->name }} @if($member->id === $currentUserId) <span class="text-xs text-gray-400">(you)</span> @endif
                            </p>
                            <p class="text-xs text-gray-500">{{ $member->email }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            @if ($member->pivot->role === 'owner')
                                <span class="text-xs font-semibold px-2 py-1 rounded-full bg-indigo-100 text-indigo-800">Owner</span>
                            @else
                                <form method="POST" action="{{ route('team.members.role', $member->id) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role" onchange="this.form.submit()" class="text-xs rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role }}" @selected($member->pivot->role === $role)>{{ ucfirst($role) }}</option>
                                        @endforeach
                                    </select>
                                </form>
                                <form method="POST" action="{{ route('team.members.remove', $member->id) }}" onsubmit="return confirm('Remove {{ $member->name }} from this organization?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-900">Remove</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
