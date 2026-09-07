<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create Email Template') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <form method="POST" action="{{ route('templates.store') }}" class="space-y-6">
                    @csrf

                    <!-- Template Name -->
                    <div>
                        <x-input-label for="name" :value="__('Template Name (Internal Reference)')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" placeholder="e.g. Welcome Newsletter" required />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <!-- Email Subject Line -->
                    <div>
                        <x-input-label for="subject" :value="__('Email Subject Line')" />
                        <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full" placeholder="e.g. Welcome to our community, @{{first_name}}!" required />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Available variables: <code class="bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded text-indigo-500">@{{first_name}}</code>, <code class="bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded text-indigo-500">@{{last_name}}</code>, <code class="bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded text-indigo-500">@{{email}}</code></p>
                        <x-input-error class="mt-2" :messages="$errors->get('subject')" />
                    </div>

                    <!-- HTML Content -->
                    <div>
                        <x-input-label for="content" :value="__('Email Body (HTML Supported)')" />
                        <textarea id="content" name="content" rows="12" required
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm font-mono text-sm"
                            placeholder="Hello @{{first_name}},&#10;&#10;Thank you for signing up for our updates!&#10;&#10;Best regards,&#10;The Team"></textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('content')" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Save Template') }}</x-primary-button>
                        <a href="{{ route('templates.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">Cancel</a>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>