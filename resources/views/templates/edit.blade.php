<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Edit Template: ') }} {{ $template->name }}
            </h2>
            <a href="{{ route('templates.index') }}" class="text-sm text-indigo-600 hover:underline font-semibold">&larr; Back to Templates</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($errors->any())
                <div class="p-4 bg-red-100 border border-red-400 text-red-800 rounded-lg shadow-sm">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Left column: edit form -->
                <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                    <form id="template-form" method="POST" action="{{ route('templates.update', $template->id) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Template Name (Internal)</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $template->name) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm sm:text-sm">
                        </div>

                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Subject Line (Optional)</label>
                            <input type="text" name="subject" id="subject" value="{{ old('subject', $template->subject) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Variables: @{{first_name}}, @{{last_name}}, @{{email}}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Template Content</label>
                            {{-- The HTML is stored in an escaped attribute and loaded into Quill from JS (never echoed raw into the page). --}}
                            <input type="hidden" name="body" id="body" value="{{ old('body', $template->body) }}">
                            @php
                                $initialMode = old('blocks_json') ? 'builder' : (!empty($template->blocks) ? 'builder' : 'html');
                                $initialBlocksJson = old('blocks_json') ?: json_encode($template->blocks ?? []);
                            @endphp
                            @include('templates._block-builder', [
                                'blockTypes' => $blockTypes,
                                'initialMode' => $initialMode,
                                'initialBlocksJson' => $initialBlocksJson,
                            ])
                        </div>

                        <div class="flex justify-end pt-4 border-t">
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-md font-bold text-xs uppercase hover:bg-indigo-700 transition">
                                Update Template
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right column: preview of the saved template -->
                <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6 flex flex-col">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Rendered Preview (last saved version)</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Simulated preview with contact: <strong>John Doe (john.doe@example.com)</strong>. Save to refresh this after builder changes.</p>

                    <div class="p-4 bg-gray-100 dark:bg-gray-900 rounded border border-gray-300 dark:border-gray-700 flex-1 flex flex-col">
                        <div class="border-b border-gray-300 dark:border-gray-700 pb-3 mb-4">
                            <span class="text-xs font-semibold uppercase text-gray-500">Subject:</span>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{{ $renderedSubject }}</p>
                        </div>

                        {{-- Sandboxed iframe: the HTML looks like the email but cannot run scripts inside the app. --}}
                        <iframe sandbox srcdoc="{{ $renderedBody }}" title="Email preview"
                            class="w-full flex-1 min-h-[24rem] bg-white rounded border border-gray-200 dark:border-gray-700"></iframe>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var quill = new Quill('#editor-container', {
                theme: 'snow',
                modules: { toolbar: [['bold', 'italic', 'underline'], [{ 'list': 'ordered' }, { 'list': 'bullet' }], ['link', 'image']] }
            });

            var bodyInput = document.getElementById('body');

            if (bodyInput.value) {
                quill.clipboard.dangerouslyPasteHTML(bodyInput.value);
            }

            // Bind to THIS form by id: document.querySelector('form') would pick the logout form in the navigation bar.
            document.getElementById('template-form').addEventListener('submit', function () {
                bodyInput.value = quill.root.innerHTML;
            });
        });
    </script>
</x-app-layout>
