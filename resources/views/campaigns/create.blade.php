<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Draft New Campaign') }}
            </h2>
            <a href="{{ route('campaigns.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline font-semibold">
                &larr; Back to Campaigns
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            
            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-800 dark:bg-red-900 dark:text-red-200 rounded-lg shadow-sm">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <form id="campaign-form" method="POST" action="{{ route('campaigns.store') }}" class="p-6 space-y-6">
                    @csrf
                    
                    <!-- Campaign Name & Subject -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Campaign Name (Internal)</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="e.g. October Newsletter" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email Subject Line</label>
                            <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required placeholder="e.g. 🚀 Big updates are here!" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                    </div>

                    <!-- Sender & Audience -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="sending_identity_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sending Identity</label>
                            <select name="sending_identity_id" id="sending_identity_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="" disabled selected>Select a verified sender...</option>
                                @foreach($identities as $identity)
                                    <option value="{{ $identity->id }}" @selected(old('sending_identity_id') == $identity->id)>{{ $identity->from_name }} &lt;{{ $identity->from_email }}&gt;</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="contact_list_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Target Audience: List</label>
                            <select name="contact_list_id" id="contact_list_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All Subscribed Contacts ({{ $totalContacts }})</option>
                                @foreach($contactLists as $list)
                                    <option value="{{ $list->id }}" @selected(old('contact_list_id') == $list->id)>{{ $list->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="segment_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Or Target Audience: Segment</label>
                            <select name="segment_id" id="segment_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">None</option>
                                @foreach($segments as $segment)
                                    <option value="{{ $segment->id }}" @selected(old('segment_id') == $segment->id)>{{ $segment->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">If a list is also chosen above, the list is used instead.</p>
                        </div>
                    </div>

                    <!-- Start from Template -->
                    <div>
                        <label for="load_template_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start from Template (Optional)</label>
                        <select id="load_template_id" class="mt-1 block w-full sm:w-1/2 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Start blank</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Loads that template's subject and content below - you can still edit both before sending. This does not change your audience selection above.</p>
                    </div>

                    <!-- WYSIWYG Email Body (Quill) -->
                    <div>
                        <label for="body" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email Content</label>
                        <!-- Hidden input to store the actual HTML output -->
                        <input type="hidden" name="body" id="body" value="{{ old('body') }}">
                        
                        <!-- Quill Editor Container -->
                        <div id="editor-container" class="bg-white text-gray-900" style="height: 400px; border-bottom-left-radius: 0.375rem; border-bottom-right-radius: 0.375rem;"></div>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit" class="inline-flex justify-center items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-bold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 transition ease-in-out duration-150">
                            Save Campaign & Snapshot Audience
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Quill.js CDN -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    
    <script>
        // Subject/body for every template in this organization, keyed by id - used by the
        // "Start from Template" selector below. Small dataset (your own templates), so this
        // is fetched once with the page rather than via a separate request.
        var automailTemplateData = @json($templates->mapWithKeys(fn ($t) => [$t->id => ['subject' => (string) $t->subject, 'body' => (string) $t->body]]));

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Quill editor with desired toolbar options
            var quill = new Quill('#editor-container', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['link', 'image', 'code-block'],
                        ['clean'] // remove formatting button
                    ]
                },
                placeholder: 'Write your beautiful email here...'
            });

            var bodyInput = document.getElementById('body');

            // Restore content after a validation error (Quill sanitizes pasted HTML).
            if (bodyInput.value) {
                quill.clipboard.dangerouslyPasteHTML(bodyInput.value);
            }

            document.getElementById('load_template_id').addEventListener('change', function (event) {
                var template = automailTemplateData[event.target.value];
                if (!template) return;

                if (template.subject) {
                    document.getElementById('subject').value = template.subject;
                }
                quill.setContents([]);
                quill.clipboard.dangerouslyPasteHTML(template.body || '');
            });

            // Bind to THIS form by id: document.querySelector('form') would pick the logout form in the navigation bar,
            // so the editor content would never reach the "body" field.
            document.getElementById('campaign-form').addEventListener('submit', function () {
                bodyInput.value = quill.root.innerHTML;
            });
        });
    </script>
</x-app-layout>