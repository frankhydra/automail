@extends('marketing.layout')

@section('title', $audience['name'].': '.config('app.name', 'AutoMail'))
@section('description', $audience['intro'])

@section('content')
<section class="relative overflow-hidden border-b border-border">
    <div class="absolute inset-0 dotgrid [mask-image:linear-gradient(to_bottom,black,transparent_85%)]" aria-hidden="true"></div>
    <div class="absolute -top-32 right-0 w-[40rem] h-[24rem] rounded-full bg-accent/20 blur-3xl" aria-hidden="true"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
        <nav aria-label="Breadcrumb" class="text-sm text-muted">
            <a href="{{ route('home') }}" class="hover:text-ink">Home</a> <span aria-hidden="true">/</span> <span>Solutions</span> <span aria-hidden="true">/</span> <span class="text-ink font-semibold">{{ $audience['name'] }}</span>
        </nav>
        <p class="mt-8 text-sm font-bold uppercase tracking-[0.16em] text-accent">{{ $audience['eyebrow'] }}</p>
        <h1 class="mt-3 max-w-3xl text-4xl sm:text-6xl font-extrabold tracking-tight leading-[1.05]">{{ $audience['title'] }}</h1>
        <p class="mt-6 max-w-2xl text-lg sm:text-xl text-muted leading-relaxed">{{ $audience['intro'] }}</p>
        <div class="mt-9 flex flex-col sm:flex-row gap-3">
            @auth
                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-7 py-3.5 rounded-xl bg-accent text-white font-bold shadow-soft hover:opacity-90 transition">Open your dashboard</a>
            @else
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-7 py-3.5 rounded-xl bg-accent text-white font-bold shadow-soft hover:opacity-90 transition">Start free</a>
            @endauth
            <a href="{{ route('marketing.pricing') }}" class="inline-flex items-center justify-center px-7 py-3.5 rounded-xl border border-border bg-card font-semibold hover:border-accent transition">See pricing</a>
        </div>
    </div>
</section>

<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-24">
    <div class="grid gap-5 sm:grid-cols-2">
        @foreach ($audience['benefits'] as $benefit)
            <div data-reveal class="rounded-2xl border border-border bg-card p-7">
                <span class="w-11 h-11 rounded-xl bg-accent/10 text-accent flex items-center justify-center">@include('marketing._icon', ['name' => $benefit['icon'], 'class' => 'w-6 h-6'])</span>
                <h2 class="mt-5 text-xl font-extrabold">{{ $benefit['title'] }}</h2>
                <p class="mt-2 text-muted leading-relaxed">{{ $benefit['text'] }}</p>
            </div>
        @endforeach
    </div>
</section>

@if (!empty($audience['code']))
    <section class="bg-sidebar text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-24">
            <div data-reveal class="max-w-2xl">
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-sun">Quick start</p>
                <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold tracking-tight">Two requests and you are connected.</h2>
            </div>
            <div class="mt-10 grid gap-6 lg:grid-cols-2">
                @foreach ($audience['code'] as $sample)
                    <div data-reveal class="min-w-0 rounded-2xl border border-sidebar-line bg-sidebar-hover/40 overflow-hidden">
                        <div class="px-5 py-3 border-b border-sidebar-line text-sm font-bold">{{ $sample['title'] }}</div>
                        <pre class="overflow-x-auto p-5 text-[13px] leading-relaxed text-[#f3d9c0]"><code>{{ $sample['code'] }}</code></pre>
                        <p class="px-5 pb-5 text-sm text-sidebar-text">{{ $sample['note'] }}</p>
                    </div>
                @endforeach
            </div>

            <div data-reveal class="mt-10 rounded-2xl border border-sidebar-line overflow-hidden">
                <div class="px-5 py-3 border-b border-sidebar-line text-sm font-bold">REST API v1 endpoints</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <tbody class="divide-y divide-sidebar-line">
                            @foreach ($audience['endpoints'] as [$verb, $path, $what])
                                <tr>
                                    <td class="px-5 py-3 w-20"><span class="rounded bg-success/25 px-2 py-0.5 text-[11px] font-bold text-[#8fd1a4]">{{ $verb }}</span></td>
                                    <td class="px-2 py-3 font-mono text-[13px] whitespace-nowrap">{{ $path }}</td>
                                    <td class="px-5 py-3 text-sidebar-text whitespace-nowrap">{{ $what }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="px-5 py-4 border-t border-sidebar-line text-sm text-sidebar-text">{{ $audience['api_note'] }} All data is scoped to your own workspace.</p>
            </div>
        </div>
    </section>
@endif

<section class="{{ empty($audience['code']) ? 'bg-paper-tint border-y border-border' : '' }}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-24 grid lg:grid-cols-2 gap-12 items-start">
        <div data-reveal>
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">A typical week</p>
            <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold tracking-tight">How it fits into your day.</h2>
            <ol class="mt-8 space-y-5">
                @foreach ($audience['flow'] as $i => $step)
                    <li class="flex gap-4">
                        <span class="flex w-9 h-9 shrink-0 items-center justify-center rounded-full bg-accent text-white font-extrabold">{{ $i + 1 }}</span>
                        <span class="pt-1.5 text-lg">{{ $step }}</span>
                    </li>
                @endforeach
            </ol>
        </div>
        <div data-reveal>
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">Tools you will use</p>
            <div class="mt-6 grid gap-4">
                @foreach ($audience['capabilities'] as $key)
                    <a href="{{ route('marketing.features') }}#{{ $key }}" class="group flex items-start gap-4 rounded-2xl border border-border bg-card p-5 hover:border-accent hover:shadow-lg transition">
                        <span class="w-11 h-11 shrink-0 rounded-xl bg-accent/10 text-accent flex items-center justify-center">@include('marketing._icon', ['name' => $capabilities[$key]['icon'], 'class' => 'w-6 h-6'])</span>
                        <span class="flex-1"><span class="block font-extrabold">{{ $capabilities[$key]['name'] }}</span><span class="block text-sm text-muted leading-snug mt-0.5">{{ $capabilities[$key]['summary'] }}</span></span>
                        <span class="mt-1 text-accent transition-transform group-hover:translate-x-1">@include('marketing._icon', ['name' => 'arrow', 'class' => 'w-5 h-5'])</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</section>

<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-4">
    <p class="text-sm font-bold uppercase tracking-[0.16em] text-muted">More solutions</p>
    <div class="mt-4 flex flex-wrap gap-3">
        @foreach ($audiences as $other => $aud)
            @continue($other === $slug)
            <a href="{{ route('marketing.audience', $other) }}" class="rounded-full border border-border bg-card px-5 py-2.5 text-sm font-semibold hover:border-accent hover:text-accent transition">{{ $aud['name'] }}</a>
        @endforeach
    </div>
</section>

<div class="pt-16">@include('marketing._cta')</div>
@endsection
