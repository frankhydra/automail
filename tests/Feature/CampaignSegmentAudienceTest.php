<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class CampaignSegmentAudienceTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function payload(array $override = []): array
    {
        return array_merge([
            'name' => 'Launch',
            'subject' => 'Hello',
            'body' => '<p>Body</p>',
        ], $override);
    }

    public function test_a_campaign_created_from_a_segment_snapshots_only_matching_contacts(): void
    {
        [$user, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org);
        $vip = $this->makeContact($org, ['tags' => 'vip']);
        $this->makeContact($org, ['tags' => 'newsletter']);

        $segment = $org->segments()->create([
            'name' => 'VIPs',
            'rules' => [['field' => 'tag', 'operator' => 'has', 'value' => 'vip']],
        ]);

        $this->actingAs($user)->post(route('campaigns.store'), $this->payload([
            'sending_identity_id' => $identity->id,
            'segment_id' => $segment->id,
        ]))->assertSessionHasNoErrors();

        $campaign = $org->campaigns()->first();

        $this->assertSame($segment->id, $campaign->segment_id);
        $this->assertSame(1, $campaign->recipients()->count());
        $this->assertSame($vip->id, $campaign->recipients()->first()->contact_id);
    }

    public function test_a_chosen_list_takes_precedence_over_a_chosen_segment(): void
    {
        [$user, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org);

        $inList = $this->makeContact($org, ['tags' => 'newsletter']);
        $list = $org->contactLists()->create(['name' => 'My list']);
        $list->contacts()->attach($inList->id);

        $inSegment = $this->makeContact($org, ['tags' => 'vip']);
        $segment = $org->segments()->create([
            'name' => 'VIPs',
            'rules' => [['field' => 'tag', 'operator' => 'has', 'value' => 'vip']],
        ]);

        $this->actingAs($user)->post(route('campaigns.store'), $this->payload([
            'sending_identity_id' => $identity->id,
            'contact_list_id' => $list->id,
            'segment_id' => $segment->id,
        ]))->assertSessionHasNoErrors();

        $campaign = $org->campaigns()->first();

        $this->assertNull($campaign->segment_id);
        $this->assertSame($inList->id, $campaign->recipients()->first()->contact_id);
    }

    public function test_a_segment_from_another_organization_is_rejected(): void
    {
        [$userA, $orgA] = $this->makeTenant('A');
        [, $orgB] = $this->makeTenant('B');

        $identity = $this->makeIdentity($orgA);
        $this->makeContact($orgA);
        $foreignSegment = $orgB->segments()->create([
            'name' => 'B segment',
            'rules' => [['field' => 'status', 'operator' => 'equals', 'value' => 'subscribed']],
        ]);

        $this->actingAs($userA)->post(route('campaigns.store'), $this->payload([
            'sending_identity_id' => $identity->id,
            'segment_id' => $foreignSegment->id,
        ]))->assertSessionHasErrors('segment_id');

        $this->assertDatabaseCount('campaigns', 0);
    }

    public function test_deleting_a_segment_does_not_delete_campaigns_created_from_it(): void
    {
        [$user, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org);
        $this->makeContact($org, ['tags' => 'vip']);

        $segment = $org->segments()->create([
            'name' => 'VIPs',
            'rules' => [['field' => 'tag', 'operator' => 'has', 'value' => 'vip']],
        ]);

        $this->actingAs($user)->post(route('campaigns.store'), $this->payload([
            'sending_identity_id' => $identity->id,
            'segment_id' => $segment->id,
        ]));

        $campaign = $org->campaigns()->first();

        $this->actingAs($user)->delete(route('segments.destroy', $segment->id));

        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id]);
        $this->assertNull($campaign->fresh()->segment_id);
        $this->assertSame(1, $campaign->recipients()->count());
    }
}
