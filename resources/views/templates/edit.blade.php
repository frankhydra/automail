<x-app-layout>
    @include('templates._editor', [
        'action' => route('templates.update', $template->id),
        'method' => 'PUT',
        'template' => $template,
        'senderLabel' => $senderLabel,
    ])
</x-app-layout>
