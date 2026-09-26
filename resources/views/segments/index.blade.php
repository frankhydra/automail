<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Audience Segments') }}
            </h2>
            <a href="{{ route('segments.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                + New Segment
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

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

            <p class="text-sm text-gray-500 dark:text-gray-400">
                A segment is a saved, reusable audience definition you can pick when creating a campaign, instead of
                typing a tag filter each time. Rules are combined with AND.
            </p>

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($segments as $segment)
                    <div class="p-6 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $segment->name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ $counts[$segment->id] ?? 0 }} matching contact(s) &middot;
                                {{ count($segment->rules) }} rule(s)
                            </p>
                        </div>
                        <div class="flex items-center gap-4">
                            <a href="{{ route('segments.edit', $segment->id) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">Edit</a>
                            <form method="POST" action="{{ route('segments.destroy', $segment->id) }}" onsubmit="return confirm('Delete this segment? Campaigns already created from it keep their contacts.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-900 dark:text-red-400">Delete</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-gray-500">
                        No segments yet. Create one to reuse an audience definition across campaigns.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
