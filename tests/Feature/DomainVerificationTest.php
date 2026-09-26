<?php

namespace Tests\Feature;

use App\Services\DnsVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenants;
use Tests\Support\FakeDnsVerificationService;
use Tests\TestCase;

class DomainVerificationTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_check_dns_verifies_only_when_all_records_are_found(): void
    {
        [$user, $org] = $this->makeTenant();

        $identity = $this->makeIdentity($org, verified: false, attributes: [
            'type' => 'custom_domain',
            'domain' => 'example.com',
            'dkim_selector' => 'automail',
            'spf_status' => 'unverified',
            'dkim_status' => 'unverified',
            'dmarc_status' => 'unverified',
        ]);

        $fakeDns = new FakeDnsVerificationService(txt: [
            '_automail.example.com' => ['automail-verification='.$identity->verification_token],
            'example.com' => ['v=spf1 include:spf.example-provider.com ~all'],
            '_dmarc.example.com' => ['v=DMARC1; p=none;'],
            // DKIM intentionally missing.
        ]);
        $this->app->instance(DnsVerificationService::class, $fakeDns);

        $this->actingAs($user)
            ->post(route('sending-identities.check-dns', $identity->id))
            ->assertSessionHasErrors('error');

        $identity->refresh();
        $this->assertSame('unverified', $identity->dkim_status);
        $this->assertSame('verified', $identity->spf_status);
        $this->assertNotSame('verified', $identity->verification_status);

        // Now DKIM is published too.
        $fakeDns->txt['automail._domainkey.example.com'] = ['v=DKIM1; k=rsa; p=abc123'];

        $this->actingAs($user)
            ->post(route('sending-identities.check-dns', $identity->id))
            ->assertSessionHas('status');

        $identity->refresh();
        $this->assertSame('verified', $identity->verification_status);
        $this->assertNotNull($identity->verified_at);
    }

    public function test_a_personal_address_cannot_be_verified_through_the_dns_endpoint(): void
    {
        [$user, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org, verified: false, attributes: ['type' => 'personal']);

        $this->actingAs($user)
            ->post(route('sending-identities.check-dns', $identity->id))
            ->assertSessionHasErrors('error');

        $this->assertNotSame('verified', $identity->fresh()->verification_status);
    }
}
