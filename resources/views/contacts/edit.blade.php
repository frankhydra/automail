<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Edit Contact') }}
            </h2>
            <a href="{{ route('contacts.index') }}" class="text-sm text-indigo-600 hover:underline font-semibold">
                &larr; Back to Contacts
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                <form method="POST" action="{{ route('contacts.update', $contact->id) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">First Name</label>
                            <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $contact->first_name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last Name</label>
                            <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $contact->last_name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email Address *</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $contact->email) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subscription Status</label>
                        <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="subscribed" {{ old('status', $contact->status) === 'subscribed' ? 'selected' : '' }}>Subscribed</option>
                            <option value="unsubscribed" {{ old('status', $contact->status) === 'unsubscribed' ? 'selected' : '' }}>Unsubscribed</option>
                            <option value="bounced" {{ old('status', $contact->status) === 'bounced' ? 'selected' : '' }}>Bounced</option>
                        </select>
                    </div>

                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-md">
                        <label for="tags" class="block text-sm font-medium text-gray-700">Tags / Segments</label>
                        <p class="text-xs text-gray-500 mb-2">Separate multiple tags with a comma (e.g. <code>VIP, customer, newsletter</code>)</p>
                        <input type="text" name="tags" id="tags" value="{{ old('tags', $contact->tags) }}" placeholder="e.g. VIP, customer" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white rounded-md font-semibold text-xs uppercase hover:bg-gray-700 transition">
                            Save Changes
                        </button>
                    </div>
                </form>
                
                <div class="mt-10 pt-6 border-t border-red-200">
                    <form method="POST" action="{{ route('contacts.destroy', $contact->id) }}" onsubmit="return confirm('Are you sure you want to permanently delete this contact?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:underline font-semibold">
                            Delete Contact
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>