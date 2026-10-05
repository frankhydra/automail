<x-app-layout>
    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-ink">Email Builder</h1>
                    <p class="text-sm text-muted mt-1">Build an email once, then start any campaign from it.</p>
                </div>
                <a href="{{ route('templates.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-accent text-white text-sm font-bold shadow-soft hover:opacity-90 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    New Template
                </a>
            </div>

            <x-flash-messages />

            @if ($templates->isEmpty())
                <div class="bg-card border border-border shadow-soft rounded-xl">
                    <x-empty-state title="No templates yet"
                        description="Build a reusable email once, then start any campaign from it instead of writing content from scratch."
                        action-label="Create Template" :action-href="route('templates.create')" />
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($templates as $template)
                        <div class="bg-card border border-border rounded-xl shadow-soft overflow-hidden flex flex-col">
                            {{-- Thumbnail: the stored email in a sandboxed (script-free) iframe, scaled down. --}}
                            <a href="{{ route('templates.edit', $template->id) }}" class="relative block h-52 overflow-hidden bg-paper-tint border-b border-border">
                                <iframe sandbox srcdoc="{{ $template->body }}" tabindex="-1" aria-hidden="true" loading="lazy"
                                        class="absolute top-0 border-0 bg-white pointer-events-none"
                                        style="width:640px;height:520px;left:50%;margin-left:-320px;transform:scale(.5);transform-origin:top center;"></iframe>
                            </a>
                            <div class="p-5 flex-1 flex flex-col justify-between gap-3">
                                <div>
                                    <h3 class="font-extrabold text-ink text-lg truncate">{{ $template->name }}</h3>
                                    <p class="text-xs text-muted mt-1 truncate">Subject: {{ $template->subject ?? 'None' }}</p>
                                </div>
                                <div class="flex justify-between items-center pt-3 border-t border-border">
                                    <span class="text-xs text-muted">{{ $template->created_at->diffForHumans() }}</span>
                                    <div class="flex items-center gap-4">
                                        <a href="{{ route('templates.edit', $template->id) }}" class="text-xs font-bold text-accent hover:underline">Edit</a>
                                        <form method="POST" action="{{ route('templates.destroy', $template->id) }}" onsubmit="return confirm('Delete this template?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-bold text-danger hover:underline">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
