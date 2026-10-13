@extends('marketing.layout')

@section('title', config('app.name', 'AutoMail').': Email marketing and automation with an AI co-writer')
@section('description', 'Grow your audience, design emails, build automated journeys and see what works. AutoMail has an AI co-writer built in, and every feature is on every plan.')

@section('content')
@php
    $plans = collect(config('plans'));
@endphp

<!-- ============================== Hero ============================== -->
<section class="relative overflow-hidden">
    <div class="absolute inset-0 dotgrid [mask-image:linear-gradient(to_bottom,black,transparent_80%)]" aria-hidden="true"></div>
    <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[60rem] h-[30rem] rounded-full bg-accent/20 blur-3xl" aria-hidden="true"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-14 sm:pt-20 pb-20 sm:pb-28 grid lg:grid-cols-2 gap-14 lg:gap-10 items-center">
        <div>
            <a href="#ai" class="inline-flex items-center gap-2 rounded-full border border-border bg-card px-3.5 py-1.5 text-xs font-bold shadow-soft hover:border-accent transition">
                <span class="text-accent">@include('marketing._icon', ['name' => 'sparkles', 'class' => 'w-4 h-4'])</span>
                AI writing assistant built in
                <span class="text-muted">@include('marketing._icon', ['name' => 'arrow', 'class' => 'w-3.5 h-3.5'])</span>
            </a>

            <h1 class="mt-6 text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-[1.05]">
                Send emails people open.
                <span class="text-accent">Automate the rest.</span>
            </h1>
            <p class="mt-6 text-lg sm:text-xl text-muted leading-relaxed max-w-xl">
                AutoMail is one place to grow your audience, design beautiful emails, build automated journeys and see what works, with an AI co-writer to get you past the blank page.
            </p>

            <div class="mt-9 flex flex-col sm:flex-row gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl bg-accent text-white font-bold shadow-soft hover:opacity-90 transition">Open your dashboard @include('marketing._icon', ['name' => 'arrow', 'class' => 'w-4 h-4'])</a>
                @else
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl bg-accent text-white font-bold shadow-soft hover:opacity-90 transition">Start free @include('marketing._icon', ['name' => 'arrow', 'class' => 'w-4 h-4'])</a>
                @endauth
                <a href="#features" class="inline-flex items-center justify-center px-7 py-3.5 rounded-xl border border-border bg-card font-semibold hover:border-accent transition">See what&rsquo;s inside</a>
            </div>
            <p class="mt-4 text-sm text-muted">Free plan included &middot; No credit card needed</p>

            <dl class="mt-10 grid grid-cols-3 gap-4 max-w-md">
                <div><dt class="sr-only">Features</dt><dd class="text-2xl font-extrabold">All</dd><dd class="text-xs text-muted">features on every plan</dd></div>
                <div><dt class="sr-only">AI</dt><dd class="text-2xl font-extrabold">AI</dd><dd class="text-xs text-muted">writing assistant included</dd></div>
                <div><dt class="sr-only">Free plan</dt><dd class="text-2xl font-extrabold">$0</dd><dd class="text-xs text-muted">to get started</dd></div>
            </dl>
        </div>

        <!-- Product illustration (sample data) -->
        <div class="relative lg:pl-6" data-reveal>
            <div class="absolute -inset-4 rounded-[2rem] bg-gradient-to-tr from-accent/30 via-sun/25 to-transparent blur-2xl" aria-hidden="true"></div>

            <div class="relative rounded-2xl border border-border bg-card shadow-2xl overflow-hidden" role="img" aria-label="Illustration of the AutoMail email builder with sample content">
                <div class="flex items-center gap-2 px-4 py-3 border-b border-border bg-paper-tint">
                    <span class="w-3 h-3 rounded-full bg-danger/70"></span><span class="w-3 h-3 rounded-full bg-sun"></span><span class="w-3 h-3 rounded-full bg-success"></span>
                    <span class="ml-3 text-xs font-semibold text-muted">Email Builder</span>
                    <span class="ml-auto text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-accent/10 text-accent">Sample</span>
                </div>
                <div class="grid grid-cols-12">
                    <div class="col-span-3 bg-sidebar p-3 space-y-1.5">
                        @foreach (['Blocks' => true, 'Envelope' => false, 'AI' => false] as $label => $on)
                            <div class="rounded-md px-2.5 py-1.5 text-[11px] font-bold {{ $on ? 'bg-accent text-white' : 'text-sidebar-text' }}">{{ $label }}</div>
                        @endforeach
                        <div class="pt-2 grid grid-cols-2 gap-1.5">
                            @foreach (range(1, 6) as $n)<div class="h-8 rounded bg-sidebar-hover"></div>@endforeach
                        </div>
                    </div>
                    <div class="col-span-9 bg-paper-tint p-4 sm:p-6">
                        <div class="rounded-lg bg-white shadow-soft overflow-hidden text-[#374151]">
                            <div class="px-5 pt-6 pb-3 text-center">
                                <div class="text-lg sm:text-xl font-extrabold text-[#111827]">The spring sale is on</div>
                                <div class="mt-1 text-xs text-[#6b7280]">Thirty percent off, this weekend only</div>
                            </div>
                            <div class="mx-5 h-24 rounded-md bg-gradient-to-br from-accent via-sun to-sun-tint"></div>
                            <div class="px-5 py-4 text-xs sm:text-sm leading-relaxed">Hello <span class="rounded bg-sun-tint px-1 font-mono text-[11px] text-[#7a4a12]">@{{first_name}}</span>, thanks for being a customer. Here is an early look at what&rsquo;s new this weekend.</div>
                            <div class="pb-6 text-center"><span class="inline-block rounded-md bg-accent px-5 py-2.5 text-xs font-bold text-white">Shop the sale</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="absolute -bottom-7 -left-2 sm:-left-8 w-60 rounded-xl border border-border bg-card p-3.5 shadow-2xl" aria-hidden="true">
                <div class="flex items-center gap-2 text-xs font-bold"><span class="text-accent">@include('marketing._icon', ['name' => 'sparkles', 'class' => 'w-4 h-4'])</span> AI draft ready</div>
                <div class="mt-2 text-xs text-muted leading-snug">&ldquo;Your early look at the spring sale&rdquo;</div>
                <div class="mt-2 flex gap-1.5"><span class="rounded-md bg-sidebar px-2 py-1 text-[10px] font-bold text-white">Insert</span><span class="rounded-md border border-border px-2 py-1 text-[10px] font-bold">Shorter</span></div>
            </div>
            <div class="absolute -top-5 -right-1 sm:-right-5 rounded-xl border border-border bg-card px-4 py-3 shadow-2xl" aria-hidden="true">
                <div class="text-[10px] font-bold uppercase tracking-wider text-muted">Open rate</div>
                <div class="text-2xl font-extrabold text-success">42.8%</div>
                <div class="text-[10px] text-muted">sample data</div>
            </div>
        </div>
    </div>
</section>

<!-- ============================== Works with ============================== -->
<section class="border-y border-border bg-paper-tint">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col md:flex-row md:items-center gap-5 md:gap-10">
        <p class="text-sm font-bold text-muted md:w-56 shrink-0">Sends through these providers and connects to these tools</p>
        <ul class="flex flex-wrap gap-x-8 gap-y-3 text-base font-extrabold tracking-tight text-muted">
            @foreach (['Brevo', 'Resend', 'Your own SMTP', 'Zapier', 'Make', 'Google Analytics'] as $name)
                <li>{{ $name }}</li>
            @endforeach
        </ul>
    </div>
</section>

<!-- ============================== Bento features ============================== -->
<section id="features" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28 scroll-mt-20">
    <div data-reveal class="max-w-2xl">
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">The whole toolkit</p>
        <h2 class="mt-3 text-3xl sm:text-5xl font-extrabold tracking-tight">Everything you need to grow by email. Nothing you don&rsquo;t.</h2>
        <p class="mt-4 text-lg text-muted">Six tools that used to be six subscriptions, working together from the first click.</p>
    </div>

    <div class="mt-12 grid gap-5 md:grid-cols-6">
        @php
            // key => grid span on medium+ screens
            $bento = ['builder' => 'md:col-span-4', 'automations' => 'md:col-span-2', 'audience' => 'md:col-span-2', 'analytics' => 'md:col-span-2', 'ai' => 'md:col-span-2', 'integrations' => 'md:col-span-6'];
        @endphp
        @foreach ($bento as $key => $span)
            @php $cap = $capabilities[$key]; $dark = $key === 'ai'; @endphp
            <a href="{{ route('marketing.features') }}#{{ $key }}" data-reveal
               class="group relative overflow-hidden rounded-2xl border p-6 sm:p-8 transition hover:-translate-y-0.5 hover:shadow-xl {{ $span }} {{ $dark ? 'bg-sidebar text-white border-sidebar-line' : 'bg-card border-border hover:border-accent' }}">
                <span class="w-11 h-11 rounded-xl flex items-center justify-center {{ $dark ? 'bg-accent text-white' : 'bg-accent/10 text-accent' }}">@include('marketing._icon', ['name' => $cap['icon'], 'class' => 'w-6 h-6'])</span>
                <h3 class="mt-5 text-xl sm:text-2xl font-extrabold tracking-tight">{{ $cap['headline'] }}</h3>
                <p class="mt-2 {{ $dark ? 'text-sidebar-text' : 'text-muted' }} leading-relaxed {{ $key === 'integrations' ? 'max-w-2xl' : '' }}">{{ $cap['summary'] }}</p>
                <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-bold {{ $dark ? 'text-sun' : 'text-accent' }}">Learn more <span class="transition-transform group-hover:translate-x-1">@include('marketing._icon', ['name' => 'arrow', 'class' => 'w-4 h-4'])</span></span>
                @if ($key === 'builder')
                    <div class="hidden sm:flex absolute right-6 bottom-6 gap-2 opacity-90" aria-hidden="true">
                        @foreach (['Header', 'Text', 'Image', 'Button'] as $b)<span class="rounded-lg border border-border bg-paper-tint px-3 py-2 text-xs font-bold">{{ $b }}</span>@endforeach
                    </div>
                @endif
            </a>
        @endforeach
    </div>
</section>

<!-- ============================== How it works ============================== -->
<section class="bg-paper-tint border-y border-border">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-24">
        <div data-reveal class="max-w-2xl">
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">How it works</p>
            <h2 class="mt-3 text-3xl sm:text-5xl font-extrabold tracking-tight">From idea to inbox in three steps.</h2>
        </div>
        <ol class="mt-12 grid gap-6 md:grid-cols-3">
            @foreach (config('marketing.steps') as $i => $step)
                <li data-reveal class="relative rounded-2xl bg-card border border-border p-7">
                    <span class="flex w-10 h-10 items-center justify-center rounded-full bg-accent text-white font-extrabold">{{ $i + 1 }}</span>
                    <h3 class="mt-5 text-xl font-extrabold">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-muted leading-relaxed">{{ $step['text'] }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

<!-- ============================== Showcase: AI ============================== -->
<section id="ai" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28 grid lg:grid-cols-2 gap-12 lg:gap-16 items-center scroll-mt-20">
    <div data-reveal>
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">AI assistant</p>
        <h2 class="mt-3 text-3xl sm:text-5xl font-extrabold tracking-tight">Never stare at a blank page again.</h2>
        <p class="mt-5 text-lg text-muted leading-relaxed">Describe the email in a sentence and get a full draft. Select any text and make it shorter, friendlier, more persuasive, or translate it. You stay in charge: nothing is sent until you say so.</p>
        <ul class="mt-6 space-y-3">
            @foreach (array_slice($capabilities['ai']['points'], 0, 4) as $point)
                <li class="flex gap-3"><span class="mt-1 text-success">@include('marketing._icon', ['name' => 'check', 'class' => 'w-5 h-5'])</span><span>{{ $point }}</span></li>
            @endforeach
        </ul>
    </div>

    <div data-reveal class="rounded-2xl border border-border bg-card shadow-xl p-5 sm:p-7" role="img" aria-label="Illustration of the AI assistant rewriting a paragraph, with sample text">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm font-bold"><span class="text-accent">@include('marketing._icon', ['name' => 'sparkles', 'class' => 'w-5 h-5'])</span> Improve with AI</div>
            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-accent/10 text-accent">Sample</span>
        </div>
        <div class="mt-5 text-[11px] font-bold uppercase tracking-wider text-muted">Before</div>
        <p class="mt-1.5 rounded-xl border border-border bg-paper p-4 text-sm leading-relaxed text-muted">We have completely revamped our core marketing engine to help you engage and convert smarter than ever before. Discover what is new in this edition.</p>
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach (['Shorter', 'Friendlier', 'More persuasive', 'Translate', 'Fix grammar'] as $i => $chip)
                <span class="rounded-full border px-3 py-1.5 text-xs font-bold {{ $i === 0 ? 'bg-accent text-white border-accent' : 'border-border bg-paper' }}">{{ $chip }}</span>
            @endforeach
        </div>
        <div class="mt-5 text-[11px] font-bold uppercase tracking-wider text-muted">After</div>
        <p class="mt-1.5 rounded-xl border-2 border-accent/50 bg-accent/5 p-4 text-sm font-semibold leading-relaxed">New this month: faster sending and simpler reports.</p>
        <div class="mt-4 flex items-center gap-3"><span class="rounded-lg bg-sidebar px-3.5 py-2 text-xs font-bold text-white">Keep this</span><span class="text-xs font-bold text-muted">Undo</span></div>
    </div>
</section>

<!-- ============================== Showcase: journeys ============================== -->
<section id="journeys" class="bg-sidebar text-white scroll-mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28 grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
        <div data-reveal class="lg:order-2">
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-sun">Automations</p>
            <h2 class="mt-3 text-3xl sm:text-5xl font-extrabold tracking-tight">Set it up once. It follows up forever.</h2>
            <p class="mt-5 text-lg text-sidebar-text leading-relaxed">Welcome every new contact, wait two days, then send a different email depending on whether they opened the first. Contacts who unsubscribe leave the journey on their own.</p>
            <ul class="mt-6 space-y-3 text-sidebar-text">
                @foreach (array_slice($capabilities['automations']['points'], 0, 4) as $point)
                    <li class="flex gap-3"><span class="mt-1 text-sun">@include('marketing._icon', ['name' => 'check', 'class' => 'w-5 h-5'])</span><span>{{ $point }}</span></li>
                @endforeach
            </ul>
            <a href="{{ route('marketing.audience', 'business') }}" class="mt-8 inline-flex items-center gap-2 font-bold text-sun hover:underline">See how a small business uses it @include('marketing._icon', ['name' => 'arrow', 'class' => 'w-4 h-4'])</a>
        </div>

        <div data-reveal class="lg:order-1 rounded-2xl border border-sidebar-line bg-sidebar-hover/40 p-6 sm:p-8" role="img" aria-label="Illustration of a journey: new contact, welcome email, wait two days, then a split on whether they opened it">
            <div class="mx-auto max-w-xs text-center space-y-0">
                <div class="rounded-xl border-2 border-white bg-sidebar p-3.5"><span class="rounded bg-sun px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-sidebar">Trigger</span><div class="mt-1.5 text-sm font-bold">A new contact is added</div></div>
                <div class="mx-auto h-5 w-px bg-sidebar-line"></div>
                <div class="rounded-xl border border-sidebar-line bg-sidebar p-3.5"><span class="rounded bg-accent/30 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white">Send email</span><div class="mt-1.5 text-sm font-bold">Welcome to the family</div></div>
                <div class="mx-auto h-5 w-px bg-sidebar-line"></div>
                <div class="rounded-full border border-sidebar-line bg-sidebar px-4 py-2 text-xs font-bold text-sidebar-text">Wait 2 days</div>
                <div class="mx-auto h-5 w-px bg-sidebar-line"></div>
                <div class="rounded-xl border-2 border-sun bg-sidebar p-3.5"><span class="rounded bg-sun/25 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-sun">Condition</span><div class="mt-1.5 text-sm font-bold">Did they open it?</div></div>
            </div>
            <div class="mt-5 grid grid-cols-2 gap-3 sm:gap-5 text-center">
                <div><span class="inline-block rounded-full bg-success/20 px-3 py-1 text-[11px] font-bold text-[#8fd1a4]">Yes</span><div class="mt-2 rounded-xl border border-sidebar-line bg-sidebar p-3 text-xs font-bold">Send the VIP offer</div></div>
                <div><span class="inline-block rounded-full bg-warning/25 px-3 py-1 text-[11px] font-bold text-[#f0c070]">No</span><div class="mt-2 rounded-xl border border-sidebar-line bg-sidebar p-3 text-xs font-bold">Send a friendly reminder</div></div>
            </div>
        </div>
    </div>
</section>

<!-- ============================== Showcase: analytics ============================== -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28 grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
    <div data-reveal>
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">Analytics</p>
        <h2 class="mt-3 text-3xl sm:text-5xl font-extrabold tracking-tight">Know what worked, and do more of it.</h2>
        <p class="mt-5 text-lg text-muted leading-relaxed">Every campaign gets its own report: who received it, who opened it, which links they clicked and when engagement peaked. Your dashboard turns the numbers into plain-language suggestions.</p>
        <a href="{{ route('marketing.features') }}#analytics" class="mt-7 inline-flex items-center gap-2 font-bold text-accent hover:underline">Explore analytics @include('marketing._icon', ['name' => 'arrow', 'class' => 'w-4 h-4'])</a>
    </div>

    <div data-reveal class="rounded-2xl border border-border bg-card shadow-xl p-5 sm:p-7" role="img" aria-label="Illustration of a campaign report with sample numbers and an hourly engagement chart">
        <div class="flex items-center justify-between"><div class="text-sm font-bold">Campaign report</div><span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-accent/10 text-accent">Sample data</span></div>
        <div class="mt-5 grid grid-cols-3 gap-3 text-center">
            @foreach ([['Delivered', '99.8%'], ['Opened', '46.8%'], ['Clicked', '14.2%']] as [$label, $value])
                <div class="rounded-xl bg-paper-tint p-3"><div class="text-xl sm:text-2xl font-extrabold">{{ $value }}</div><div class="text-[11px] font-bold uppercase tracking-wider text-muted">{{ $label }}</div></div>
            @endforeach
        </div>
        <div class="mt-6 text-[11px] font-bold uppercase tracking-wider text-muted">Opens in the first 24 hours</div>
        <div class="mt-3 flex h-36 items-end gap-1.5 sm:gap-2">
            @foreach ([22, 60, 100, 78, 55, 42, 30, 24, 18, 15, 12, 10] as $h)
                <div class="flex-1 rounded-t-md bg-accent" style="height: {{ $h }}%; opacity: {{ 0.45 + $h / 200 }}"></div>
            @endforeach
        </div>
        <div class="mt-5 rounded-xl bg-paper-tint p-3.5 text-sm"><span class="font-bold">Insight:</span> <span class="text-muted">most opens come within three hours. Send when your audience is awake.</span></div>
    </div>
</section>

<!-- ============================== Solutions ============================== -->
<section class="bg-paper-tint border-y border-border">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-24">
        <div data-reveal class="max-w-2xl">
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">Solutions</p>
            <h2 class="mt-3 text-3xl sm:text-5xl font-extrabold tracking-tight">Built for how you work.</h2>
        </div>
        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($audiences as $slug => $aud)
                <a href="{{ route('marketing.audience', $slug) }}" data-reveal class="group rounded-2xl border border-border bg-card p-6 hover:border-accent hover:shadow-xl transition">
                    <span class="w-11 h-11 rounded-xl bg-sun/25 flex items-center justify-center">@include('marketing._icon', ['name' => $aud['icon'], 'class' => 'w-6 h-6'])</span>
                    <h3 class="mt-5 text-lg font-extrabold">{{ $aud['name'] }}</h3>
                    <p class="mt-2 text-sm text-muted leading-relaxed">{{ $aud['menu'] }}</p>
                    <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-bold text-accent">Explore <span class="transition-transform group-hover:translate-x-1">@include('marketing._icon', ['name' => 'arrow', 'class' => 'w-4 h-4'])</span></span>
                </a>
            @endforeach
        </div>
    </div>
</section>

<!-- ============================== Trust ============================== -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28">
    <div data-reveal class="max-w-3xl">
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">Deliverability and control</p>
        <h2 class="mt-3 text-3xl sm:text-5xl font-extrabold tracking-tight">Built to reach the inbox, and to keep you on the right side of the rules.</h2>
    </div>
    <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach (config('marketing.included') as $item)
            <div data-reveal class="rounded-2xl border border-border bg-card p-6">
                <span class="w-10 h-10 rounded-xl bg-accent/10 text-accent flex items-center justify-center">@include('marketing._icon', ['name' => $item['icon'], 'class' => 'w-5 h-5'])</span>
                <h3 class="mt-4 text-lg font-extrabold">{{ $item['title'] }}</h3>
                <p class="mt-2 text-sm text-muted leading-relaxed">{{ $item['text'] }}</p>
            </div>
        @endforeach
    </div>
</section>

<!-- ============================== Pricing teaser ============================== -->
<section class="bg-paper-tint border-y border-border">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-24">
        <div data-reveal class="max-w-2xl">
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">Pricing</p>
            <h2 class="mt-3 text-3xl sm:text-5xl font-extrabold tracking-tight">Simple plans. Every feature on every plan.</h2>
            <p class="mt-4 text-lg text-muted">You only pay for volume. Start free and move up when you need more room.</p>
        </div>
        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($plans as $key => $plan)
                @php [$amount, $period] = array_pad(explode('/', $plan['price_display'], 2), 2, 'mo'); $limits = $plan['limits']; @endphp
                <div data-reveal class="rounded-2xl border bg-card p-6 {{ $key === 'business' ? 'border-accent shadow-xl' : 'border-border' }}">
                    <div class="text-sm font-bold text-muted">{{ $plan['name'] }}</div>
                    <div class="mt-2 flex items-baseline gap-1"><span class="text-4xl font-extrabold">{{ $amount }}</span><span class="text-sm text-muted">/{{ $period }}</span></div>
                    <ul class="mt-5 space-y-2 text-sm">
                        <li>{{ ($limits['contacts'] ?? null) === null ? 'Unlimited' : number_format($limits['contacts']) }} contacts</li>
                        <li>{{ ($limits['monthly_emails'] ?? null) === null ? 'Unlimited' : number_format($limits['monthly_emails']) }} emails a month</li>
                        <li>{{ ($limits['team_members'] ?? null) === null ? 'Unlimited' : $limits['team_members'] }} team members</li>
                    </ul>
                </div>
            @endforeach
        </div>
        <a href="{{ route('marketing.pricing') }}" class="mt-8 inline-flex items-center gap-2 font-bold text-accent hover:underline">Compare plans in detail @include('marketing._icon', ['name' => 'arrow', 'class' => 'w-4 h-4'])</a>
    </div>
</section>

<!-- ============================== FAQ ============================== -->
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28">
    <h2 data-reveal class="text-3xl sm:text-4xl font-extrabold tracking-tight text-center">Questions, answered</h2>
    <div data-reveal class="mt-10">
        @include('marketing._faq', ['items' => array_slice(config('marketing.faqs.general'), 0, 6)])
    </div>
</section>

@include('marketing._cta')
@endsection
