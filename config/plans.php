<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AutoMail plans
    |--------------------------------------------------------------------------
    | "limits" values are per-organization; null means unlimited. These are
    | example numbers, not a pricing decision - adjust freely. See
    | App\Services\PlanLimitService for how they are checked and enforced.
    | There is no payment processor wired to these yet (see the Billing page) -
    | this file only defines what each plan allows.
    */

    'free' => [
        'name' => 'Free',
        'price_display' => '$0/mo',
        'limits' => [
            'contacts' => 250,
            'monthly_emails' => 500,
            'team_members' => 3,
            'sending_identities' => 1,
        ],
    ],

    'starter' => [
        'name' => 'Starter',
        'price_display' => '$19/mo',
        'limits' => [
            'contacts' => 2500,
            'monthly_emails' => 10000,
            'team_members' => 3,
            'sending_identities' => 3,
        ],
    ],

    'business' => [
        'name' => 'Business',
        'price_display' => '$49/mo',
        'limits' => [
            'contacts' => 25000,
            'monthly_emails' => 100000,
            'team_members' => 10,
            'sending_identities' => 10,
        ],
    ],

    'pro' => [
        'name' => 'Pro',
        'price_display' => '$99/mo',
        'limits' => [
            'contacts' => null,
            'monthly_emails' => null,
            'team_members' => null,
            'sending_identities' => null,
        ],
    ],

];
