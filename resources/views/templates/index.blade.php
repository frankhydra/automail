<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Email Templates') }}
            </h2>
            <a href="{{ route('templates.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                + Create Template
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Status Messages -->
            @if (session('status'))
                <div class="p-4 bg-green-100 border border-green-400 text-green-700 dark:bg-green-900 dark:text-green-200 dark:border-green-700 rounded-lg shadow-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 bg-red-100 border border-red-400 text-red-700 dark:bg-red-900 dark:text-red-200 dark:border-red-700 rounded-lg shadow-sm">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                @if ($templates->isEmpty())
                    <div class="text-center py-8">
                        <p class="text-gray-500 dark:text-gray-400 text-sm">No email templates created yet.</p>
                        <a href="{{ route('templates.create') }}" class="mt-4 inline-block text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                            Create your first template &rarr;
                        </a>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($templates as $template)
                            <div class="p-5 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900/80 flex flex-col justify-between">
                                <div>
                                    <h4 class="font-semibold text-lg text-gray-900 dark:text-gray-100">{{ $template->name }}</h4>
                                    <p class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold mt-1 truncate">
                                        Subject: {{ $template->subject }}
                                    </p>
                                    <div class="mt-3 p-3 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 text-xs text-gray-700 dark:text-gray-300 h-24 overflow-hidden text-ellipsis">
                                        {!! strip_tags($template->content) !!}
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">{{ $template->created_at->diffForHumans() }}</span>
                                    <div class="flex space-x-3">
                                        <a href="{{ route('templates.edit', $template->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-semibold">
                                            Edit / Preview
                                        </a>
                                        <form method="POST" action="{{ route('templates.destroy', $template->id) }}" onsubmit="return confirm('Delete this template?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 dark:text-red-400 hover:underline font-semibold">
                                                Delete
                                            </button>
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