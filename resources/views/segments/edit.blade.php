<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Edit Segment: ') }} {{ $segment->name }}
            </h2>
            <a href="{{ route('segments.index') }}" class="text-sm text-indigo-600 hover:underline font-semibold">&larr; Back to Segments</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

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
                <form method="POST" action="{{ route('segments.update', $segment->id) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Segment Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $segment->name) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm sm:text-sm">
                    </div>

                    <div>
                        <h3 class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Rules</h3>
                        @php
                            if (old('rule_field')) {
                                $rebuilt = [];
                                foreach (old('rule_field') as $i => $field) {
                                    $rebuilt[] = [
                                        'field' => $field,
                                        'operator' => old('rule_operator')[$i] ?? '',
                                        'value' => old('rule_value')[$i] ?? '',
                                    ];
                                }
                                $initialRulesJson = json_encode($rebuilt);
                            } else {
                                $initialRulesJson = json_encode($segment->rules ?? []);
                            }
                        @endphp
                        @include('segments._rule-builder', ['operators' => $operators, 'initialRulesJson' => $initialRulesJson])
                    </div>

                    <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-md font-bold text-xs uppercase hover:bg-indigo-700 transition">
                            Update Segment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
