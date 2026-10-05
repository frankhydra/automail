<x-app-layout>
    @include('templates._editor', [
        'action' => route('templates.store'),
        'method' => 'POST',
        'template' => null,
        'senderLabel' => $senderLabel,
    ])
</x-app-layout>
