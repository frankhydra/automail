<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Create Reusable Template') }}
            </h2>
            <a href="{{ route('templates.index') }}" class="text-sm text-indigo-600 hover:underline font-semibold">&larr; Back to Templates</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-800 rounded-lg shadow-sm">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                <form id="template-form" method="POST" action="{{ route('templates.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Template Name (Internal)</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="e.g. Standard Newsletter Boilerplate" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm sm:text-sm">
                    </div>

                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Subject Line (Optional)</label>
                        <input type="text" name="subject" id="subject" value="{{ old('subject') }}" placeholder="e.g. Weekly Updates from AutoMail" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Variables: @{{first_name}}, @{{last_name}}, @{{email}}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Template Content</label>
                        <input type="hidden" name="body" id="body" value="{{ old('body') }}">
                        @include('templates._block-builder', [
                            'blockTypes' => $blockTypes,
                            'initialMode' => 'builder',
                            'initialBlocksJson' => old('blocks_json', '[]'),
                        ])
                    </div>

                    <div class="flex justify-end pt-4 border-t">
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-md font-bold text-xs uppercase hover:bg-indigo-700 transition">
                            Save Template
                        </button>
                    </div>
                </form>
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

            // Restore content after a validation error (Quill sanitizes pasted HTML).
            if (bodyInput.value) {
                quill.clipboard.dangerouslyPasteHTML(bodyInput.value);
            }

            // Bind to THIS form by id: document.querySelector('form') would pick the logout form in the navigation bar.
            // Harmless when the Visual Builder is used instead - the "body" field is simply ignored server-side
            // whenever blocks_json is present (see TemplateController::resolveContent).
            document.getElementById('template-form').addEventListener('submit', function () {
                bodyInput.value = quill.root.innerHTML;
            });
        });
    </script>
</x-app-layout>
