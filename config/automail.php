<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Active email delivery provider
    |--------------------------------------------------------------------------
    | Mirrors EMAIL_PROVIDER (see EmailDeliveryServiceProvider). Used here only
    | to pick the right DNS preset below - it does not select the provider itself.
    */
    'active_provider' => env('EMAIL_PROVIDER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Per-provider DNS presets
    |--------------------------------------------------------------------------
    | SPF "include" host and DKIM selector are stable, publicly documented
    | values for each provider. DKIM's actual public-key VALUE is different
    | for every domain and is issued by the provider when you add that domain
    | in their dashboard/API - it can't be a fixed constant here, so it stays
    | null and the customer pastes in the value their provider gave them.
    |
    | SES is intentionally absent: AutoMail sends to SES over plain SMTP (see
    | EmailDeliveryServiceProvider), and SES's DKIM setup is three CNAME
    | records unique per domain (via "Easy DKIM" in the AWS console) rather
    | than one TXT record, so it doesn't fit this single-selector shape. Until
    | SES gets its own instructions here, its identities fall back to the
    | generic preset below and the customer follows the AWS console directly.
    */
    'provider_presets' => [
        'brevo' => [
            'spf_include' => 'spf.brevo.com',
            'dkim_selector' => 'mail',
        ],
        'resend' => [
            'spf_include' => '_spf.resend.com',
            'dkim_selector' => 'resend',
        ],
        'smtp' => [
            'spf_include' => env('AUTOMAIL_SPF_INCLUDE', 'spf.example-provider.com'),
            'dkim_selector' => env('AUTOMAIL_DKIM_SELECTOR', 'automail'),
        ],
        'log' => [
            'spf_include' => 'spf.example-provider.com',
            'dkim_selector' => 'automail',
        ],
    ],

    // Value (TXT value or CNAME target) supplied by your provider for this domain.
    // Leave empty to accept any DKIM record found at the selector.
    'dkim_value' => env('AUTOMAIL_DKIM_VALUE'),

    // Recommended DMARC record shown to users.
    'dmarc_record' => env('AUTOMAIL_DMARC_RECORD', 'v=DMARC1; p=none;'),

    // Host (relative to the domain) where the ownership TXT record is published.
    'ownership_host' => '_automail',

    // Free mailbox providers: these can only be used as "personal" identities,
    // verified by clicking a link sent to the mailbox.
    'personal_domains' => [
        'gmail.com', 'googlemail.com', 'yahoo.com', 'outlook.com', 'hotmail.com',
        'live.com', 'msn.com', 'icloud.com', 'me.com', 'proton.me', 'protonmail.com',
        'aol.com', 'gmx.com', 'yandex.com', 'zoho.com',
    ],
];
