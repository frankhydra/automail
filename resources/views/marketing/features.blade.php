@extends('marketing.layout')

@section('title', 'Features: '.config('app.name', 'AutoMail'))
@section('description', 'The email builder, automations, audience tools, analytics, AI assistant and integrations that make up AutoMail. Every feature is on every plan.')

@section('content')
<section class="relative overflow-hidden border-b border-border">
    <div class="absolute inset-0 dotgrid [mask-image:linear-gradient(to_bottom,black,transparent_85%)]" aria-hidden="true"></div>
    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 text-center">
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">Features</p>
        <h1 class="mt-3 text-4xl sm:text-6xl font-extrabold tracking-tight leading-[1.05]">Everything you need to grow by email.</h1>
        <p class="mt-6 text-lg sm:text-xl text-muted leading-relaxed max-w-2xl mx-auto">Six tools that work together from the first click. And because every plan includes every feature, you never have to upgrade to unlock something you need.</p>
        <nav class="mt-8 flex flex-wrap justify-center gap-2" aria-label="Jump to a feature">
            @foreach ($menuCapabilities as $key)
                <a href="#{{ $key }}" class="rounded-full border border-border bg-card px-4 py-2 text-sm font-semibold hover:border-accent hover:text-accent transition">{{ $capabilities[$key]['name'] }}</a>
            @endforeach
        </nav>
    </div>
</section>

@foreach ($menuCapabilities as $i => $key)
    @php $cap = $capabilities[$key]; $flip = $i % 2 === 1; @endphp
    <section id="{{ $key }}" class="scroll-mt-20 {{ $flip ? 'bg-paper-tint border-y border-border' : '' }}">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 grid lg:grid-cols-2 gap-10 lg:gap-16 items-center">
            <div data-reveal class="{{ $flip ? 'lg:order-2' : '' }}">
                <span class="w-12 h-12 rounded-xl bg-accent/10 text-accent flex items-center justify-center">@include('marketing._icon', ['name' => $cap['icon'], 'class' => 'w-6 h-6'])</span>
                <p class="mt-5 text-sm font-bold uppercase tracking-[0.16em] text-accent">{{ $cap['name'] }}</p>
                <h2 class="mt-2 text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $cap['headline'] }}</h2>
                <p class="mt-4 text-lg text-muted leading-relaxed">{{ $cap['summary'] }}</p>
                <ul class="mt-6 space-y-3">
                    @foreach ($cap['points'] as $point)
                        <li class="flex gap-3"><span class="mt-1 text-success shrink-0">@include('marketing._icon', ['name' => 'check', 'class' => 'w-5 h-5'])</span><span>{{ $point }}</span></li>
                    @endforeach
                </ul>
            </div>

            <div data-reveal class="{{ $flip ? 'lg:order-1' : '' }}" aria-hidden="true">
                <div class="relative overflow-hidden rounded-3xl border border-border aspect-[4/3] flex items-center justify-center bg-gradient-to-br from-accent via-accent to-sun">
                    <div class="absolute inset-0 dotgrid opacity-40"></div>
                    <div class="absolute -top-16 -right-10 w-64 h-64 rounded-full bg-white/15 blur-2xl"></div>
                    <div class="relative flex flex-col items-center gap-4 text-white">
                        <span class="w-24 h-24 sm:w-28 sm:h-28 rounded-3xl bg-white/20 backdrop-blur flex items-center justify-center shadow-2xl">@include('marketing._icon', ['name' => $cap['icon'], 'class' => 'w-12 h-12 sm:w-14 sm:h-14'])</span>
                        <span class="text-xl sm:text-2xl font-extrabold tracking-tight">{{ $cap['name'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endforeach

<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28">
    <div data-reveal class="max-w-2xl">
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">Also included</p>
        <h2 class="mt-3 text-3xl sm:text-5xl font-extrabold tracking-tight">The details that make it dependable.</h2>
    </div>
    <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach (config('marketing.included') as $item)
            @php
                $text = $item['text'];
                if ($item['icon'] === 'palette' && $themeCount > 1) {
                    $text = "Choose from {$themeCount} colour themes and switch between light and dark mode. Your workspace, your look.";
                }
            @endphp
            <div data-reveal class="rounded-2xl border border-border bg-card p-6">
                <span class="w-10 h-10 rounded-xl bg-accent/10 text-accent flex items-center justify-center">@include('marketing._icon', ['name' => $item['icon'], 'class' => 'w-5 h-5'])</span>
                <h3 class="mt-4 text-lg font-extrabold">{{ $item['title'] }}</h3>
                <p class="mt-2 text-sm text-muted leading-relaxed">{{ $text }}</p>
            </div>
        @endforeach
    </div>
</section>

@include('marketing._cta', ['title' => 'See it for yourself.', 'text' => 'Create a free account and build your first email in minutes. No credit card needed.'])
@endsection
