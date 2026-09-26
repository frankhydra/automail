<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_an_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/contacts')->assertUnauthorized();
    }

    public function test_a_valid_token_can_list_contacts_scoped_to_its_own_organization(): void
    {
        [$user, $org] = $this->makeTenant();
        $this->makeContact($org, ['first_name' => 'Alice']);
        [, $otherOrg] = $this->makeTenant('Other');
        $this->makeContact($otherOrg, ['first_name' => 'Bob']);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/contacts')
            ->assertOk();

        $names = collect($response->json('data'))->pluck('first_name');
        $this->assertTrue($names->contains('Alice'));
        $this->assertFalse($names->contains('Bob'));
    }

    public function test_show_a_single_contact_rejects_one_from_another_organization(): void
    {
        [$user, $org] = $this->makeTenant();
        [, $otherOrg] = $this->makeTenant('Other');
        $foreignContact = $this->makeContact($otherOrg);

        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/contacts/{$foreignContact->id}")
            ->assertNotFound();
    }

    public function test_sending_identity_resource_never_exposes_the_verification_token(): void
    {
        [$user, $org] = $this->makeTenant();
        $this->makeIdentity($org);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/sending-identities')
            ->assertOk();

        $response->assertJsonMissingPath('data.0.verification_token');
    }

    public function test_campaign_show_includes_stats_but_index_does_not(): void
    {
        [$user, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org);
        $contact = $this->makeContact($org);
        $campaign = $this->makeCampaign($org, $identity, [$contact]);
        $campaign->recipients()->update(['status' => 'sent']);

        $token = $user->createToken('test')->plainTextToken;

        $indexResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/campaigns')
            ->assertOk();
        $this->assertArrayNotHasKey('stats', $indexResponse->json('data.0'));

        $showResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/campaigns/{$campaign->id}")
            ->assertOk();
        $this->assertSame(1, $showResponse->json('data.stats.sent'));
    }

    public function test_analytics_endpoint_returns_a_summary(): void
    {
        [$user, $org] = $this->makeTenant();
        $this->makeContact($org);
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/analytics')
            ->assertOk()
            ->assertJsonStructure(['contacts' => ['total'], 'campaigns' => ['total'], 'emails' => ['sent']]);
    }

    public function test_a_revoked_token_no_longer_works(): void
    {
        [$user] = $this->makeTenant();
        $token = $user->createToken('test');
        $plainText = $token->plainTextToken;

        $user->tokens()->where('id', $token->accessToken->id)->delete();

        $this->withHeader('Authorization', "Bearer {$plainText}")
            ->getJson('/api/v1/contacts')
            ->assertUnauthorized();
    }
}
