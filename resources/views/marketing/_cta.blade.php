{{-- Closing call-to-action band. Optional $title / $text. --}}
<section class="px-4 sm:px-6 lg:px-8 pb-20">
    <div data-reveal class="max-w-7xl mx-auto relative overflow-hidden rounded-3xl bg-sidebar text-white px-6 sm:px-12 py-14 sm:py-20 text-center">
        <div class="absolute -top-24 -left-24 w-80 h-80 rounded-full bg-accent/40 blur-3xl" aria-hidden="true"></div>
        <div class="absolute -bottom-28 -right-20 w-96 h-96 rounded-full bg-sun/30 blur-3xl" aria-hidden="true"></div>
        <div class="relative">
            <h2 class="text-3xl sm:text-5xl font-extrabold tracking-tight max-w-3xl mx-auto">{{ $title ?? 'Your next campaign starts here.' }}</h2>
            <p class="mt-5 text-lg text-sidebar-text max-w-2xl mx-auto">{{ $text ?? 'Create a free account, import your audience and send your first email today. No credit card needed.' }}</p>
            <div class="mt-9 flex flex-col sm:flex-row items-center justify-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="w-full sm:w-auto px-7 py-3.5 rounded-xl bg-sun text-sidebar font-bold hover:brightness-105 transition">Open your dashboard</a>
                @else
                    <a href="{{ route('register') }}" class="w-full sm:w-auto px-7 py-3.5 rounded-xl bg-sun text-sidebar font-bold hover:brightness-105 transition">Start free</a>
                    <a href="{{ route('marketing.pricing') }}" class="w-full sm:w-auto px-7 py-3.5 rounded-xl border border-sidebar-line text-white font-semibold hover:bg-sidebar-hover transition">See pricing</a>
                @endauth
            </div>
        </div>
    </div>
</section>
