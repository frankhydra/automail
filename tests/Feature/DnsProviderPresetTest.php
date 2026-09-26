<?php

namespace Tests\Feature;

use App\Services\DnsVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class DnsProviderPresetTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_expected_records_use_the_brevo_preset_when_brevo_is_active(): void
    {
        config(['automail.active_provider' => 'brevo']);

        [, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org, verified: false, attributes: [
            'type' => 'custom_domain',
            'domain' => 'example.com',
            'dkim_selector' => null,
        ]);

        $records = app(DnsVerificationService::class)->expectedRecords($identity);
        $spf = collect($records)->firstWhere('key', 'spf');
        $dkim = collect($records)->firstWhere('key', 'dkim');

        $this->assertStringContainsString('spf.brevo.com', $spf['value']);
        $this->assertStringContainsString('mail._domainkey', $dkim['host']);
    }

    public function test_expected_records_use_the_resend_preset_when_resend_is_active(): void
    {
        config(['automail.active_provider' => 'resend']);

        [, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org, verified: false, attributes: [
            'type' => 'custom_domain',
            'domain' => 'example.com',
            'dkim_selector' => null,
        ]);

        $records = app(DnsVerificationService::class)->expectedRecords($identity);
        $spf = collect($records)->firstWhere('key', 'spf');
        $dkim = collect($records)->firstWhere('key', 'dkim');

        $this->assertStringContainsString('_spf.resend.com', $spf['value']);
        $this->assertStringContainsString('resend._domainkey', $dkim['host']);
    }

    public function test_an_identitys_own_dkim_selector_overrides_the_provider_preset(): void
    {
        config(['automail.active_provider' => 'resend']);

        [, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org, verified: false, attributes: [
            'type' => 'custom_domain',
            'domain' => 'example.com',
            'dkim_selector' => 'custom123',
        ]);

        $records = app(DnsVerificationService::class)->expectedRecords($identity);
        $dkim = collect($records)->firstWhere('key', 'dkim');

        $this->assertStringContainsString('custom123._domainkey', $dkim['host']);
    }
}
