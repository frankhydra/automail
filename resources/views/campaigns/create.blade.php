<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create Email Campaign') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <form method="POST" action="{{ route('campaigns.store') }}" class="space-y-6" x-data="{ templateSelected: '' }">
                    @csrf

                    <!-- Campaign Name -->
                    <div>
                        <x-input-label for="name" :value="__('Campaign Name (Internal Reference)')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" placeholder="e.g. August Special Offer" required />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <!-- Verified Sending Identity Selection -->
                    <div>
                        <x-input-label for="sending_identity_id" :value="__('From Address (Verified Sender)')" />
                        <select id="sending_identity_id" name="sending_identity_id" required
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                            @foreach ($identities as $identity)
                                <option value="{{ $identity->id }}">
                                    {{ $identity->from_name }} &lt;{{ $identity->from_email }}&gt;
                                </option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('sending_identity_id')" />
                    </div>

                    <!-- Target Audience / List Selection -->
                    <div>
                        <x-input-label for="contact_list_id" :value="__('Target Recipient Audience')" />
                        <select id="contact_list_id" name="contact_list_id"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                            <option value="">All Subscribed Contacts ({{ $totalContacts }} contacts)</option>
                            @foreach ($contactLists as $list)
                                <option value="{{ $list->id }}">{{ $list->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('contact_list_id')" />
                    </div>

                    <!-- Subject Line -->
                    <div>
                        <x-input-label for="subject" :value="__('Email Subject Line')" />
                        <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full" placeholder="e.g. Special offer for @{{first_name}}!" required />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Supported merge tags: <code class="bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded text-indigo-500">@{{first_name}}</code>, <code class="bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded text-indigo-500">@{{last_name}}</code>, <code class="bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded text-indigo-500">@{{email}}</code></p>
                        <x-input-error class="mt-2" :messages="$errors->get('subject')" />
                    </div>

                    <!-- Email Content Body -->
                    <div>
                        <x-input-label for="body" :value="__('Email Body Content (HTML Supported)')" />
                        <textarea id="body" name="body" rows="10" required
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm font-mono text-sm"
                            placeholder="Hello @{{first_name}},&#10;&#10;Here is your exclusive update!"></textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('body')" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Save & Freeze Campaign Audience') }}</x-primary-button>
                        <a href="{{ route('campaigns.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">Cancel</a>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>