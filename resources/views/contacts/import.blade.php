<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Import Contacts (CSV)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Status Messages -->
            @if (session('status'))
                <div class="p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg shadow-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('import_errors') && count(session('import_errors')) > 0)
                <div class="p-4 bg-yellow-100 border border-yellow-400 text-yellow-800 rounded-lg shadow-sm">
                    <p class="font-semibold mb-2">Import Warnings / Skipped Rows:</p>
                    <ul class="list-disc list-inside text-sm space-y-1">
                        @foreach (session('import_errors') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Import Form Card -->
            <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <header>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ __('Upload Contact Spreadsheet') }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('Upload a CSV file containing columns: first_name, last_name, and email.') }}
                    </p>
                </header>

                <form method="POST" action="{{ route('contacts.import.store') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
                    @csrf

                    <!-- CSV File Input -->
                    <div>
                        <x-input-label for="csv_file" :value="__('Select CSV File')" />
                        <input id="csv_file" name="csv_file" type="file" accept=".csv, .txt" required
                            class="mt-1 block w-full text-sm text-gray-900 dark:text-gray-300 border border-gray-300 dark:border-gray-700 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 focus:outline-none" />
                        <x-input-error class="mt-2" :messages="$errors->get('csv_file')" />
                    </div>

                    <!-- Target Contact List Selection -->
                    <div>
                        <x-input-label for="contact_list_id" :value="__('Assign to Existing Contact List (Optional)')" />
                        <select id="contact_list_id" name="contact_list_id"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                            <option value="">-- None / Do not assign to list --</option>
                            @foreach ($lists as $list)
                                <option value="{{ $list->id }}">{{ $list->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('contact_list_id')" />
                    </div>

                    <!-- Create New List on the Fly -->
                    <div>
                        <x-input-label for="new_list_name" :value="__('Or Create a New List for this Import')" />
                        <x-text-input id="new_list_name" name="new_list_name" type="text" class="mt-1 block w-full" placeholder="e.g. August 2026 Promo Leads" />
                        <x-input-error class="mt-2" :messages="$errors->get('new_list_name')" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Upload & Import Contacts') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <!-- Recent Contacts Table -->
            <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <header class="mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ __('Recently Added Contacts') }}
                    </h3>
                </header>

                @if ($recentContacts->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">No contacts imported yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">First Name</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Last Name</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-sm text-gray-700 dark:text-gray-300">
                                @foreach ($recentContacts as $contact)
                                    <tr>
                                        <td class="px-4 py-2 font-medium">{{ $contact->email }}</td>
                                        <td class="px-4 py-2">{{ $contact->first_name ?? '—' }}</td>
                                        <td class="px-4 py-2">{{ $contact->last_name ?? '—' }}</td>
                                        <td class="px-4 py-2">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                {{ $contact->status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-xs text-gray-400">{{ $contact->created_at->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>