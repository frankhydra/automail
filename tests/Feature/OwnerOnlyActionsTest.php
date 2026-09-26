<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class OwnerOnlyActionsTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_a_non_owner_cannot_delete_a_campaign(): void
    {
        [$owner, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org);
        $contact = $this->makeContact($org);
        $campaign = $this->makeCampaign($org, $identity, [$contact]);

        $member = $this->attachMember($org, 'editor');

        $this->actingAs($member)
            ->delete(route('campaigns.destroy', $campaign->id))
            ->assertForbidden();

        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id]);
    }

    public function test_the_owner_can_delete_a_campaign(): void
    {
        [$owner, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org);
        $contact = $this->makeContact($org);
        $campaign = $this->makeCampaign($org, $identity, [$contact]);

        $this->actingAs($owner)
            ->delete(route('campaigns.destroy', $campaign->id))
            ->assertRedirect(route('campaigns.index'));

        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
    }

    public function test_a_non_owner_cannot_delete_a_sending_identity(): void
    {
        [, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org);
        $member = $this->attachMember($org, 'viewer');

        $this->actingAs($member)
            ->delete(route('sending-identities.destroy', $identity->id))
            ->assertForbidden();
    }
}
