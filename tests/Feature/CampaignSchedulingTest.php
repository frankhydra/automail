<?php

namespace Tests\Feature;

use App\Console\Commands\ProcessScheduledCampaigns;
use App\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class CampaignSchedulingTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_scheduling_a_campaign_sets_status_and_time_without_sending_immediately(): void
    {
        [$user, $org] = $this->makeTenant();
        $provider = $this->useFakeProvider();

        $identity = $this->makeIdentity($org);
        $contact = $this->makeContact($org);
        $campaign = $this->makeCampaign($org, $identity, [$contact]);

        $future = now()->addHour()->format('Y-m-d\TH:i');

        $this->actingAs($user)->post(route('campaigns.dispatch', $campaign->id), [
            'mode' => 'schedule',
            'scheduled_at' => $future,
            'tag_filter' => 'vip',
        ])->assertSessionHasNoErrors();

        $campaign->refresh();
        $this->assertSame('scheduled', $campaign->status);
        $this->assertSame('vip', $campaign->tag_filter);
        $this->assertNotNull($campaign->scheduled_at);
        $this->assertCount(0, $provider->sent);
    }

    public function test_scheduling_in_the_past_is_rejected(): void
    {
        [$user, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org);
        $contact = $this->makeContact($org);
        $campaign = $this->makeCampaign($org, $identity, [$contact]);

        $this->actingAs($user)->post(route('campaigns.dispatch', $campaign->id), [
            'mode' => 'schedule',
            'scheduled_at' => now()->subHour()->format('Y-m-d\TH:i'),
        ])->assertSessionHasErrors('scheduled_at');

        $this->assertSame('draft', $campaign->fresh()->status);
    }

    public function test_the_scheduler_command_dispatches_due_campaigns_and_leaves_future_ones_alone(): void
    {
        [, $org] = $this->makeTenant();
        $provider = $this->useFakeProvider();

        $identity = $this->makeIdentity($org);
        $dueContact = $this->makeContact($org, ['email' => 'due@example.com']);
        $futureContact = $this->makeContact($org, ['email' => 'future@example.com']);

        $due = $this->makeCampaign($org, $identity, [$dueContact], [
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinute(),
        ]);

        $future = $this->makeCampaign($org, $identity, [$futureContact], [
            'status' => 'scheduled',
            'scheduled_at' => now()->addHour(),
        ]);

        $this->artisan(ProcessScheduledCampaigns::class)->assertSuccessful();

        $this->assertSame('sent', $due->fresh()->status);
        $this->assertSame('scheduled', $future->fresh()->status);
        $this->assertCount(1, $provider->sent);
        $this->assertSame('due@example.com', $provider->sent[0]['toEmail']);
    }

    public function test_the_scheduler_command_replays_the_tag_filter_that_was_saved_when_scheduling(): void
    {
        [, $org] = $this->makeTenant();
        $provider = $this->useFakeProvider();

        $identity = $this->makeIdentity($org);
        $vip = $this->makeContact($org, ['email' => 'vip@example.com', 'tags' => 'vip']);
        $regular = $this->makeContact($org, ['email' => 'regular@example.com', 'tags' => null]);

        $campaign = $this->makeCampaign($org, $identity, [$vip, $regular], [
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinute(),
            'tag_filter' => 'vip',
        ]);

        $this->artisan(ProcessScheduledCampaigns::class);

        $this->assertCount(1, $provider->sent);
        $this->assertSame('vip@example.com', $provider->sent[0]['toEmail']);
        $this->assertSame('sent', $campaign->fresh()->status);
    }

    public function test_a_scheduled_campaign_can_be_cancelled_and_the_scheduler_then_skips_it(): void
    {
        [$user, $org] = $this->makeTenant();
        $provider = $this->useFakeProvider();

        $identity = $this->makeIdentity($org);
        $contact = $this->makeContact($org);
        $campaign = $this->makeCampaign($org, $identity, [$contact], [
            'status' => 'scheduled',
            'scheduled_at' => now()->addMinute(),
        ]);

        $this->actingAs($user)
            ->post(route('campaigns.cancel', $campaign->id))
            ->assertRedirect(route('campaigns.show', $campaign->id));

        $this->assertSame('cancelled', $campaign->fresh()->status);

        // Even if time has since passed, a cancelled campaign must never be picked up.
        $campaign->update(['scheduled_at' => now()->subMinute()]);
        $this->artisan(ProcessScheduledCampaigns::class);

        $this->assertSame('cancelled', $campaign->fresh()->status);
        $this->assertCount(0, $provider->sent);
    }

    public function test_cancel_fails_for_a_campaign_that_is_not_scheduled(): void
    {
        [$user, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org);
        $contact = $this->makeContact($org);
        $campaign = $this->makeCampaign($org, $identity, [$contact]); // draft

        $this->actingAs($user)
            ->post(route('campaigns.cancel', $campaign->id))
            ->assertSessionHasErrors('error');

        $this->assertSame('draft', $campaign->fresh()->status);
    }

    public function test_a_non_owner_can_still_cancel_since_cancel_has_no_owner_restriction(): void
    {
        // Cancel is not a destructive delete, so any org member may use it -
        // documenting that decision so it's not mistaken for an oversight.
        [, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org);
        $contact = $this->makeContact($org);
        $campaign = $this->makeCampaign($org, $identity, [$contact], [
            'status' => 'scheduled',
            'scheduled_at' => now()->addHour(),
        ]);

        $member = $this->attachMember($org, 'editor');

        $this->actingAs($member)
            ->post(route('campaigns.cancel', $campaign->id))
            ->assertRedirect(route('campaigns.show', $campaign->id));

        $this->assertSame('cancelled', $campaign->fresh()->status);
    }
}
