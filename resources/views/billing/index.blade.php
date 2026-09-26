<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Billing & Plan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-green-100 border border-green-400 text-green-800 rounded-lg shadow-sm">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="p-4 bg-red-100 border border-red-400 text-red-800 rounded-lg shadow-sm">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="p-4 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-800 rounded-md text-sm text-blue-800 dark:text-blue-200">
                This is a demo billing screen: switching plans below is immediate and free - no payment is processed.
                A production version would route "Switch" through a real payment provider.
            </div>

            <!-- Current usage -->
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
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
                                <span class="text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ $usage[$key]['used'] }} / {{ $usage[$key]['limit'] ?? 'Unlimited' }}
                                </span>
                            </div>
                            @if ($usage[$key]['percent'] !== null)
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ $usage[$key]['percent'] >= 100 ? 'bg-red-500' : ($usage[$key]['percent'] >= 80 ? 'bg-yellow-500' : 'bg-indigo-500') }}"
                                         style="width: {{ $usage[$key]['percent'] }}%"></div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Plan cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                @foreach ($plans as $key => $plan)
                    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6 flex flex-col {{ $key === $currentPlanKey ? 'ring-2 ring-indigo-500' : '' }}">
                        <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $plan['name'] }}</h4>
                        <p class="text-2xl font-bold text-indigo-600 my-2">{{ $plan['price_display'] }}</p>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1 flex-1 mb-4">
                            <li>{{ $plan['limits']['contacts'] ?? 'Unlimited' }} contacts</li>
                            <li>{{ $plan['limits']['monthly_emails'] ?? 'Unlimited' }} emails/mo</li>
                            <li>{{ $plan['limits']['team_members'] ?? 'Unlimited' }} team members</li>
                            <li>{{ $plan['limits']['sending_identities'] ?? 'Unlimited' }} sending identities</li>
                        </ul>
                        @if ($key === $currentPlanKey)
                            <span class="text-center text-xs font-semibold uppercase tracking-widest text-indigo-600 py-2">Current Plan</span>
                        @else
                            <form method="POST" action="{{ route('billing.upgrade') }}">
                                @csrf
                                <input type="hidden" name="plan" value="{{ $key }}">
                                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                    Switch to {{ $plan['name'] }}
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
