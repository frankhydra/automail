<x-app-layout>
    @include('automations._editor', [
        'action' => route('automations.update', $automation->id),
        'method' => 'PUT',
        'automation' => $automation,
    ])
</x-app-layout>
