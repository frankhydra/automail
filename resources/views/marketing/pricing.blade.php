@extends('marketing.layout')

@section('title', 'Pricing: '.config('app.name', 'AutoMail'))
@section('description', 'Simple plans that scale with your list. Every feature is on every plan: you only pay for volume. Start free.')

@section('content')
@php
    $fmt = fn ($n) => $n === null ? 'Unlimited' : number_format($n);
    $finder = [
        'plans' => collect($plans)->map(fn ($p) => [
            'key' => $p['key'], 'name' => $p['name'], 'price' => $p['amount'].$p['period'],
            'contacts' => $p['contacts'], 'emails' => $p['emails'],
        ])->values(),
    ];
@endphp

<section class="relative overflow-hidden border-b border-border">
    <div class="absolute inset-0 dotgrid [mask-image:linear-gradient(to_bottom,black,transparent_85%)]" aria-hidden="true"></div>
    <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[50rem] h-[22rem] rounded-full bg-sun/25 blur-3xl" aria-hidden="true"></div>
    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 sm:pt-24 pb-14 text-center">
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">Pricing</p>
        <h1 class="mt-3 text-4xl sm:text-6xl font-extrabold tracking-tight leading-[1.05]">Simple plans. Every feature on every plan.</h1>
        <p class="mt-6 text-lg sm:text-xl text-muted leading-relaxed max-w-2xl mx-auto">The builder, automations, segments, analytics, AI assistant, integrations and API are included from the free plan up. You only pay for volume.</p>
    </div>
</section>

<!-- Plans -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-2 pt-12 pb-20">
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4 items-stretch">
        @foreach ($plans as $plan)
            <div data-reveal class="relative flex flex-col rounded-2xl border bg-card p-7 {{ $plan['featured'] ? 'border-accent shadow-2xl lg:-mt-3 lg:pb-10' : 'border-border' }}">
                @if ($plan['featured'])
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-accent px-3.5 py-1 text-[11px] font-bold uppercase tracking-wider text-white">{{ $plan['blurb'] }}</span>
                @endif
                <h2 class="text-lg font-extrabold">{{ $plan['name'] }}</h2>
                <p class="mt-1 text-sm text-muted min-h-[2.5rem]">{{ $plan['featured'] ? 'More room for contacts, sends and teammates.' : $plan['blurb'] }}</p>
                <div class="mt-5 flex items-baseline gap-1"><span class="text-5xl font-extrabold tracking-tight">{{ $plan['amount'] }}</span><span class="text-muted">{{ $plan['period'] }}</span></div>

                <ul class="mt-6 space-y-3 text-sm flex-1">
                    @foreach ([
                        [$fmt($plan['contacts']), 'contacts'],
                        [$fmt($plan['emails']), 'emails a month'],
                        [$fmt($plan['team']), 'team members'],
                        [$fmt($plan['identities']), 'sending identities'],
                        [$fmt($plan['ai']), 'AI requests a month'],
                    ] as [$value, $label])
                        <li class="flex gap-3"><span class="mt-0.5 text-success shrink-0">@include('marketing._icon', ['name' => 'check', 'class' => 'w-4 h-4'])</span><span><strong>{{ $value }}</strong> {{ $label }}</span></li>
                    @endforeach
                    <li class="flex gap-3 text-muted"><span class="mt-0.5 text-success shrink-0">@include('marketing._icon', ['name' => 'check', 'class' => 'w-4 h-4'])</span><span>Every feature included</span></li>
                </ul>

                @auth
                    <a href="{{ route('billing.index') }}" class="mt-8 inline-flex items-center justify-center rounded-xl px-5 py-3 font-bold transition {{ $plan['featured'] ? 'bg-accent text-white hover:opacity-90' : 'border border-border hover:border-accent' }}">Manage plan</a>
                @else
                    <a href="{{ route('register') }}" class="mt-8 inline-flex items-center justify-center rounded-xl px-5 py-3 font-bold transition {{ $plan['featured'] ? 'bg-accent text-white hover:opacity-90' : 'border border-border hover:border-accent' }}">{{ $plan['key'] === 'free' ? 'Start free' : 'Get started' }}</a>
                @endauth
            </div>
        @endforeach
    </div>
    <p class="mt-6 text-center text-sm text-muted">Start on any plan and change it later from Settings, Billing.</p>
</section>

<!-- Plan finder -->
<section class="bg-paper-tint border-y border-border">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div data-reveal class="text-center">
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">Plan finder</p>
            <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold tracking-tight">Which plan fits your list?</h2>
            <p class="mt-3 text-muted">Tell us roughly how big your audience is and how often you email.</p>
        </div>

        <div x-data="planFinder(@js($finder))" data-reveal class="mt-10 rounded-2xl border border-border bg-card p-6 sm:p-9 shadow-soft">
            <div class="grid gap-8 sm:grid-cols-2">
                <div>
                    <label for="pf-contacts" class="flex items-center justify-between text-sm font-bold">Contacts <span class="rounded-lg bg-sun/30 px-3 py-1 text-base font-extrabold" x-text="format(contacts)"></span></label>
                    <input id="pf-contacts" type="range" min="0" :max="contactSteps.length - 1" step="1" x-model.number="contactIndex" class="mt-4 w-full accent-[rgb(var(--accent))]">
                    <div class="mt-1 flex justify-between text-[11px] text-muted"><span>100</span><span>100,000+</span></div>
                </div>
                <div>
                    <label for="pf-freq" class="flex items-center justify-between text-sm font-bold">Emails per contact each month <span class="rounded-lg bg-sun/30 px-3 py-1 text-base font-extrabold" x-text="perMonth"></span></label>
                    <input id="pf-freq" type="range" min="1" max="12" step="1" x-model.number="perMonth" class="mt-4 w-full accent-[rgb(var(--accent))]">
                    <div class="mt-1 flex justify-between text-[11px] text-muted"><span>1</span><span>12</span></div>
                </div>
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-3 text-center">
                <div class="rounded-xl bg-paper-tint p-4"><div class="text-[11px] font-bold uppercase tracking-wider text-muted">Emails a month</div><div class="mt-1 text-2xl font-extrabold" x-text="format(sends)"></div></div>
                <div class="rounded-xl border-2 border-accent bg-accent/5 p-4"><div class="text-[11px] font-bold uppercase tracking-wider text-muted">Recommended plan</div><div class="mt-1 text-2xl font-extrabold text-accent" x-text="plan.name"></div></div>
                <div class="rounded-xl bg-paper-tint p-4"><div class="text-[11px] font-bold uppercase tracking-wider text-muted">Price</div><div class="mt-1 text-2xl font-extrabold" x-text="plan.price"></div></div>
            </div>

            <p class="mt-5 text-center text-xs text-muted">Automation emails count toward your monthly total, so leave some room if you run journeys.</p>
        </div>
    </div>
</section>

<!-- Included in every plan -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-24">
    <div data-reveal class="max-w-2xl">
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">In every plan</p>
        <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold tracking-tight">No locked features. Ever.</h2>
    </div>
    <ul class="mt-10 grid gap-x-8 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($menuCapabilities as $key)
            @foreach (array_slice($capabilities[$key]['points'], 0, 2) as $point)
                <li data-reveal class="flex gap-3"><span class="mt-1 text-success shrink-0">@include('marketing._icon', ['name' => 'check', 'class' => 'w-5 h-5'])</span><span>{{ $point }}</span></li>
            @endforeach
        @endforeach
    </ul>
</section>

<!-- Comparison table -->
<section class="bg-paper-tint border-y border-border">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <h2 data-reveal class="text-3xl sm:text-4xl font-extrabold tracking-tight text-center">Compare the limits</h2>
        <div data-reveal class="mt-10 overflow-x-auto rounded-2xl border border-border bg-card">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th scope="col" class="px-5 py-4 font-bold text-muted">What you get</th>
                        @foreach ($plans as $plan)<th scope="col" class="px-5 py-4 font-extrabold {{ $plan['featured'] ? 'text-accent' : '' }}">{{ $plan['name'] }}</th>@endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach ([['Price', null], ['Contacts', 'contacts'], ['Emails a month', 'emails'], ['Team members', 'team'], ['Sending identities', 'identities'], ['AI requests a month', 'ai']] as [$label, $field])
                        <tr>
                            <th scope="row" class="px-5 py-4 text-left font-semibold">{{ $label }}</th>
                            @foreach ($plans as $plan)
                                <td class="px-5 py-4 {{ $plan['featured'] ? 'bg-accent/5 font-bold' : '' }}">{{ $field === null ? $plan['amount'].$plan['period'] : $fmt($plan[$field]) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    <tr>
                        <th scope="row" class="px-5 py-4 text-left font-semibold">Every feature</th>
                        @foreach ($plans as $plan)<td class="px-5 py-4 text-success {{ $plan['featured'] ? 'bg-accent/5' : '' }}">@include('marketing._icon', ['name' => 'check', 'class' => 'w-5 h-5'])<span class="sr-only">Included</span></td>@endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28">
    <h2 data-reveal class="text-3xl sm:text-4xl font-extrabold tracking-tight text-center">Pricing questions</h2>
    <div data-reveal class="mt-10">@include('marketing._faq', ['items' => config('marketing.faqs.pricing')])</div>
</section>

@include('marketing._cta', ['title' => 'Start free. Upgrade when you outgrow it.', 'text' => 'Every feature is included from day one. Create your account and send your first email today.'])

<script>
    document.addEventListener('alpine:init', function () {
        Alpine.data('planFinder', function (cfg) {
            return {
                plans: cfg.plans,
                contactSteps: [100, 250, 500, 1000, 2500, 5000, 10000, 25000, 50000, 100000],
                contactIndex: 3,
                perMonth: 4,

                get contacts() { return this.contactSteps[this.contactIndex]; },
                get sends() { return this.contacts * this.perMonth; },

                // The smallest plan whose contact AND monthly-email limits both fit ("null" = unlimited).
                get plan() {
                    var contacts = this.contacts, sends = this.sends;
                    var fit = this.plans.find(function (p) {
                        return (p.contacts === null || contacts <= p.contacts) && (p.emails === null || sends <= p.emails);
                    });
                    return fit || this.plans[this.plans.length - 1];
                },

                format: function (n) { return n.toLocaleString('en-US') + (n >= 100000 ? '+' : ''); }
            };
        });
    });
</script>
@endsection
