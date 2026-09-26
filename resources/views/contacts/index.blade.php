<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Contacts Management') }}
            </h2>
            <a href="{{ route('contacts.import.show') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition ease-in-out duration-150">
                Import CSV
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if (session('status'))
                <div class="p-4 bg-green-100 border border-green-400 text-green-800 rounded-lg shadow-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                <form method="GET" action="{{ route('contacts.index') }}" class="mb-6 flex gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email, or tag..." class="w-full md:w-1/3 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <button type="submit" class="px-4 py-2 bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-800 text-sm font-semibold rounded-md hover:bg-gray-700 transition">
                        Search
                    </button>
                    @if(request('search'))
                        <a href="{{ route('contacts.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-semibold rounded-md hover:bg-gray-300 transition">Clear</a>
                    @endif
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-500 dark:text-gray-300 uppercase">Name</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-500 dark:text-gray-300 uppercase">Email</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-500 dark:text-gray-300 uppercase">Tags</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @forelse ($contacts as $contact)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $contact->first_name }} {{ $contact->last_name }}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $contact->email }}</td>
                                    <td class="px-4 py-3">
                                        @if($contact->status === 'subscribed')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Subscribed</span>
                                        @elseif($contact->status === 'unsubscribed')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Unsubscribed</span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Bounced</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                        @if($contact->tags)
                                            @foreach(explode(',', $contact->tags) as $tag)
                                                <span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 text-xs rounded border border-indigo-200 mr-1">{{ trim($tag) }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-gray-400 text-xs italic">No tags</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('contacts.edit', $contact->id) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">Edit</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                        No contacts found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $contacts->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>