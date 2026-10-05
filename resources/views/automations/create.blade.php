<x-app-layout>
    @include('automations._editor', [
        'action' => route('automations.store'),
        'method' => 'POST',
        'automation' => null,
    ])
</x-app-layout>
