{{-- Accessible accordion with no JavaScript: <details>/<summary>. Expects $items = [['q' => ..., 'a' => ...], ...]. --}}
<div class="divide-y divide-border rounded-2xl border border-border bg-card overflow-hidden">
    @foreach ($items as $item)
        <details class="group">
            <summary class="flex items-center justify-between gap-4 px-5 sm:px-6 py-5 cursor-pointer font-bold hover:bg-paper-tint focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-accent">
                <span>{{ $item['q'] }}</span>
                <span class="faq-chevron shrink-0 text-muted transition-transform">@include('marketing._icon', ['name' => 'chevron', 'class' => 'w-5 h-5'])</span>
            </summary>
            <p class="px-5 sm:px-6 pb-6 -mt-1 text-muted leading-relaxed max-w-3xl">{{ $item['a'] }}</p>
        </details>
    @endforeach
</div>
