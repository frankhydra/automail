@php
    $usedPercent = $capBytes > 0 ? min(100, (int) round($usedBytes / $capBytes * 100)) : 0;
    $usedLabel = number_format($usedBytes / 1048576, 1);
    $capLabel = (int) round($capBytes / 1048576);
@endphp

<x-app-layout>
    <div class="py-8" x-data="{ copied: null }">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-ink">Content Studio</h1>
                    <p class="text-sm text-muted mt-1">Asset library for the images and logos you use in your emails.</p>
                </div>

                @if ($canUpload)
                    <form method="POST" action="{{ route('assets.store') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="file" id="asset-file" accept="image/png,image/jpeg,image/gif,image/webp" class="hidden" onchange="this.form.submit()">
                        <label for="asset-file" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-accent text-white text-sm font-bold shadow-soft hover:opacity-90 transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                            Upload Asset
                        </label>
                    </form>
                @endif
            </div>

            <x-flash-messages />

            @if ($errors->has('file'))
                <div class="p-4 bg-danger-tint border border-danger/30 text-danger rounded-lg text-sm">{{ $errors->first('file') }}</div>
            @endif

            @if ($localOnly && $assets->isNotEmpty())
                <div class="p-4 bg-warning-tint border border-warning/30 text-ink rounded-lg text-sm">
                    <strong>Heads up:</strong> your <code>APP_URL</code> points to a local address, so these images will not show for people who receive your emails. Once AutoMail is online with its real address in <code>APP_URL</code>, the image links will work.
                </div>
            @endif

            <!-- Storage meter -->
            <div class="bg-card border border-border rounded-xl shadow-soft px-6 py-4">
                <div class="flex items-center justify-between text-xs font-semibold text-muted mb-2">
                    <span>Image storage</span>
                    <span class="text-ink">{{ $usedLabel }} MB of {{ $capLabel }} MB used</span>
                </div>
                <div class="h-2 rounded-full bg-paper-tint overflow-hidden"><div class="h-full rounded-full bg-sun" style="width: {{ $usedPercent }}%"></div></div>
                <p class="text-[11px] text-muted mt-2">JPG, PNG, GIF or WebP, up to 5 MB each. SVG is not supported because most email apps cannot display it.</p>
            </div>

            @if ($assets->isEmpty())
                <div class="bg-card border border-border shadow-soft rounded-xl">
                    <x-empty-state title="No images yet"
                        description="Upload a logo or photo once, then drop it into any email from the Email Builder."
                        :action-label="$canUpload ? 'Upload an image' : null" :action-href="null" />
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    @foreach ($assets as $asset)
                        <div class="bg-card border border-border rounded-xl shadow-soft overflow-hidden flex flex-col">
                            <div class="h-40 bg-paper-tint border-b border-border flex items-center justify-center overflow-hidden">
                                <img src="{{ $asset->url() }}" alt="{{ $asset->name }}" loading="lazy" class="max-h-full max-w-full object-contain">
                            </div>
                            <div class="p-4 flex-1 flex flex-col justify-between gap-3">
                                <div>
                                    <div class="font-bold text-ink text-sm truncate" title="{{ $asset->name }}">{{ $asset->name }}</div>
                                    <div class="flex items-center justify-between text-[11px] text-muted mt-1">
                                        <span>{{ $asset->typeLabel() }}@if ($asset->width) &middot; {{ $asset->width }}&times;{{ $asset->height }}@endif</span>
                                        <span>{{ $asset->formattedSize() }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between pt-3 border-t border-border">
                                    <button type="button" @click="navigator.clipboard.writeText(@js($asset->url())); copied = {{ $asset->id }}; setTimeout(() => copied = null, 1500)"
                                            class="text-xs font-bold text-accent hover:underline" x-text="copied === {{ $asset->id }} ? 'Copied!' : 'Copy link'"></button>
                                    @if ($canDelete)
                                        <form method="POST" action="{{ route('assets.destroy', $asset->id) }}" onsubmit="return confirm('Delete this image? Emails that still use it will show a broken picture.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-bold text-danger hover:underline">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
