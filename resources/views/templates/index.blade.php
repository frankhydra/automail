<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Email Templates') }}
            </h2>
            <a href="{{ route('templates.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                Create Template
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

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                @if($templates->isEmpty())
                    <p class="text-sm text-gray-500 text-center py-6">No saved templates yet. Create your first reusable template above!</p>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @foreach($templates as $template)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900 flex flex-col justify-between">
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-gray-100 text-lg mb-1">{{ $template->name }}</h3>
                                    <p class="text-xs text-gray-500 mb-3">Default Subject: {{ $template->subject ?? 'None' }}</p>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 p-3 rounded border max-h-32 overflow-hidden mb-4">
                                        {{ \Illuminate\Support\Str::limit(strip_tags((string) $template->body), 120) }}
                                    </div>
                                </div>
                                <div class="flex justify-between items-center pt-2 border-t border-gray-200 dark:border-gray-800">
                                    <span class="text-xs text-gray-400">{{ $template->created_at->diffForHumans() }}</span>
                                    <div class="flex items-center space-x-3">
                                        <a href="{{ route('templates.edit', $template->id) }}" class="text-xs font-semibold text-indigo-600 hover:underline">Edit / Preview</a>
                                        <form method="POST" action="{{ route('templates.destroy', $template->id) }}" onsubmit="return confirm('Delete this template?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
