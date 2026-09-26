<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class CampaignDispatchTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_dispatch_uses_the_list_chosen_when_the_campaign_was_created(): void
    {
        [$user, $org] = $this->makeTenant();
        $provider = $this->useFakeProvider();

        $identity = $this->makeIdentity($org);
        $inList = $this->makeContact($org, ['email' => 'in-list@example.com']);
        $this->makeContact($org, ['email' => 'not-in-list@example.com']);

        $list = $org->contactLists()->create(['name' => 'Chosen']);
        $list->contacts()->attach($inList->id);

        $this->actingAs($user)->post(route('campaigns.store'), [
            'name' => 'Listed',
            'subject' => 'Hi',
            'body' => '<p>Body</p>',
            'sending_identity_id' => $identity->id,
            'contact_list_id' => $list->id,
        ])->assertSessionHasNoErrors();

        $campaign = $org->campaigns()->first();

        $this->actingAs($user)->post(route('campaigns.dispatch', $campaign->id));

        $this->assertCount(1, $provider->sent);
        $this->assertSame('in-list@example.com', $provider->sent[0]['toEmail']);
        $this->assertSame(1, $campaign->recipients()->count());
        $this->assertSame('sent', $campaign->fresh()->status);
    }

    public function test_tag_filter_matches_whole_tags_only(): void
    {
        [$user, $org] = $this->makeTenant();
        $provider = $this->useFakeProvider();

        $identity = $this->makeIdentity($org);
        $vip = $this->makeContact($org, ['email' => 'vip@example.com', 'tags' => 'VIP, newsletter']);
        $nonVip = $this->makeContact($org, ['email' => 'nonvip@example.com', 'tags' => 'non-VIP']);
        $untagged = $this->makeContact($org, ['email' => 'plain@example.com', 'tags' => null]);

        $campaign = $this->makeCampaign($org, $identity, [$vip, $nonVip, $untagged]);

        $this->actingAs($user)->post(route('campaigns.dispatch', $campaign->id), ['tag_filter' => 'vip']);

        $this->assertCount(1, $provider->sent);
        $this->assertSame('vip@example.com', $provider->sent[0]['toEmail']);
        $this->assertSame(2, $campaign->recipients()->where('status', 'skipped')->count());
    }

    public function test_dispatch_skips_contacts_who_unsubscribed_after_the_snapshot(): void
    {
        [$user, $org] = $this->makeTenant();
        $provider = $this->useFakeProvider();

        $identity = $this->makeIdentity($org);
        $stays = $this->makeContact($org, ['email' => 'stays@example.com']);
        $leaves = $this->makeContact($org, ['email' => 'leaves@example.com']);

        $campaign = $this->makeCampaign($org, $identity, [$stays, $leaves]);

        $leaves->update(['status' => 'unsubscribed']);

        $this->actingAs($user)->post(route('campaigns.dispatch', $campaign->id));

        $this->assertCount(1, $provider->sent);
        $this->assertSame('stays@example.com', $provider->sent[0]['toEmail']);
        $this->assertSame('skipped', $campaign->recipients()->where('contact_id', $leaves->id)->first()->status);
    }

    public function test_a_tag_filter_with_no_matches_leaves_the_campaign_untouched(): void
    {
        [$user, $org] = $this->makeTenant();
        $provider = $this->useFakeProvider();

        $identity = $this->makeIdentity($org);
        $contact = $this->makeContact($org, ['tags' => 'newsletter']);
        $campaign = $this->makeCampaign($org, $identity, [$contact]);

        $this->actingAs($user)
            ->post(route('campaigns.dispatch', $campaign->id), ['tag_filter' => 'zzz'])
            ->assertSessionHasErrors('error');

        $this->assertSame('draft', $campaign->fresh()->status);
        $this->assertSame('pending', $campaign->recipients()->first()->status);
        $this->assertCount(0, $provider->sent);
    }

    public function test_a_campaign_cannot_be_dispatched_twice(): void
    {
        [$user, $org] = $this->makeTenant();
        $provider = $this->useFakeProvider();

        $identity = $this->makeIdentity($org);
        $contact = $this->makeContact($org);
        $campaign = $this->makeCampaign($org, $identity, [$contact]);

        $this->actingAs($user)->post(route('campaigns.dispatch', $campaign->id));

        $this->actingAs($user)
            ->post(route('campaigns.dispatch', $campaign->id))
            ->assertSessionHasErrors('error');

        $this->assertCount(1, $provider->sent);
    }
}
