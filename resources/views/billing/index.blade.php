<x-settings-layout active="billing">
    <div class="p-4 bg-info-tint border border-accent/20 rounded-md text-sm text-accent">
        This is a demo billing screen: switching plans below is immediate and free - no payment is processed.
        A production version would route "Switch" through a real payment provider.
    </div>

    <!-- Current usage -->
    <div class="bg-card border border-border shadow-sm rounded-lg p-6">
        <h3 class="text-lg font-medium text-ink mb-4">
            Current Plan: {{ $plans[$currentPlanKey]['name'] }}
        </h3>
        <div class="space-y-4">
            @foreach ([
                'contacts' => 'Contacts',
                'monthly_emails' => 'Emails sent this month',
                'team_members' => 'Team members',
                'sending_identities' => 'Sending identities',
            ] as $key => $label)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-ink">{{ $label }}</span>
                        <span class="text-muted">
                            {{ $usage[$key]['used'] }} / {{ $usage[$key]['limit'] ?? 'Unlimited' }}
                        </span>
                    </div>
                    @if ($usage[$key]['percent'] !== null)
                        <div class="w-full bg-paper-tint rounded-full h-2">
                            <div class="h-2 rounded-full {{ $usage[$key]['percent'] >= 100 ? 'bg-danger' : ($usage[$key]['percent'] >= 80 ? 'bg-warning' : 'bg-accent') }}"
                                 style="width: {{ $usage[$key]['percent'] }}%"></div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Plan cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        @foreach ($plans as $key => $plan)
            <div class="bg-card border {{ $key === $currentPlanKey ? 'border-accent ring-1 ring-accent' : 'border-border' }} shadow-sm rounded-lg p-6 flex flex-col">
                <h4 class="text-lg font-bold text-ink">{{ $plan['name'] }}</h4>
                <p class="text-2xl font-bold text-accent my-2">{{ $plan['price_display'] }}</p>
                <ul class="text-sm text-muted space-y-1 flex-1 mb-4">
                    <li>{{ $plan['limits']['contacts'] ?? 'Unlimited' }} contacts</li>
                    <li>{{ $plan['limits']['monthly_emails'] ?? 'Unlimited' }} emails/mo</li>
                    <li>{{ $plan['limits']['team_members'] ?? 'Unlimited' }} team members</li>
                    <li>{{ $plan['limits']['sending_identities'] ?? 'Unlimited' }} sending identities</li>
                </ul>
                @if ($key === $currentPlanKey)
                    <span class="text-center text-xs font-semibold uppercase tracking-widest text-accent py-2">Current Plan</span>
                @else
                    <form method="POST" action="{{ route('billing.upgrade') }}">
                        @csrf
                        <input type="hidden" name="plan" value="{{ $key }}">
                        <button type="submit" class="w-full px-4 py-2 bg-accent border border-transparent rounded-md font-semibold text-xs text-paper uppercase tracking-widest hover:opacity-90">
                            Switch to {{ $plan['name'] }}
                        </button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>
</x-settings-layout>
