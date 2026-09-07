<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit & Preview Template') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Left Column: Edit Form -->
                <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Edit Template</h3>
                    
                    <form method="POST" action="{{ route('templates.update', $template->id) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="name" :value="__('Template Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $template->name)" required />
                        </div>

                        <div>
                            <x-input-label for="subject" :value="__('Subject Line')" />
                            <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full" :value="old('subject', $template->subject)" required />
                        </div>

                        <div>
                            <x-input-label for="content" :value="__('HTML Content Body')" />
                            <textarea id="content" name="content" rows="12" required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm font-mono text-sm">{{ old('content', $template->content) }}</textarea>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Update Template') }}</x-primary-button>
                            <a href="{{ route('templates.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">Cancel</a>
                        </div>
                    </form>
                </div>

                <!-- Right Column: Live Rendered Preview -->
                <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg flex flex-col">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Live Rendered Preview</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Simulated preview with contact: <strong>John Doe (john.doe@example.com)</strong></p>

                    <div class="p-4 bg-gray-100 dark:bg-gray-900 rounded border border-gray-300 dark:border-gray-700 flex-1 flex flex-col">
                        <div class="border-b border-gray-300 dark:border-gray-700 pb-3 mb-4">
                            <span class="text-xs font-semibold uppercase text-gray-500">Subject:</span>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{{ $renderedSubject }}</p>
                        </div>

                        <div class="text-sm text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-800 p-4 rounded border border-gray-200 dark:border-gray-700 flex-1 overflow-auto">
                            {!! nl2br($renderedBody) !!}
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>