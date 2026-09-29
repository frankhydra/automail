<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-ink leading-tight">
                {{ __('Email Templates') }}
            </h2>
            <a href="{{ route('templates.create') }}" class="inline-flex items-center px-4 py-2 bg-accent border border-transparent rounded-md font-semibold text-xs text-paper uppercase tracking-widest hover:opacity-90 transition">
                + Create Template
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-flash-messages />

            @if($templates->isEmpty())
                <div class="bg-card border border-border shadow-sm rounded-xl">
                    <x-empty-state
                        title="No templates yet"
                        description="Build a reusable email once, then start any campaign from it instead of writing content from scratch."
                        action-label="Create Template"
                        :action-href="route('templates.create')" />
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($templates as $template)
                        <div class="border border-border rounded-xl p-5 bg-card shadow-sm flex flex-col justify-between">
                            <div>
                                <h3 class="font-bold text-ink text-lg mb-1">{{ $template->name }}</h3>
                                <p class="text-xs text-muted mb-3">Default Subject: {{ $template->subject ?? 'None' }}</p>
                                <div class="text-xs text-muted bg-paper-tint p-3 rounded-md border border-border max-h-32 overflow-hidden mb-4">
                                    {{ \Illuminate\Support\Str::limit(strip_tags((string) $template->body), 120) }}
                                </div>
                            </div>
                            <div class="flex justify-between items-center pt-3 border-t border-border">
                                <span class="text-xs text-muted">{{ $template->created_at->diffForHumans() }}</span>
                                <div class="flex items-center space-x-3">
                                    <a href="{{ route('templates.edit', $template->id) }}" class="text-xs font-semibold text-accent hover:underline">Edit / Preview</a>
                                    <form method="POST" action="{{ route('templates.destroy', $template->id) }}" onsubmit="return confirm('Delete this template?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-danger hover:underline">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
