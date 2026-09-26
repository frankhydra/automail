<?php

namespace Tests\Feature;

use App\Services\PlanLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class PlanLimitTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_csv_import_stops_at_the_free_plans_contact_limit(): void
    {
        [$user, $org] = $this->makeTenant();
        $org->update(['plan' => 'free']); // limit: 250 contacts

        // Fill the org to exactly one slot remaining.
        for ($i = 0; $i < 249; $i++) {
            $this->makeContact($org);
        }

        $csv = "email\nfits@example.com\noverflow@example.com\n";
        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csv);

        $this->actingAs($user)->post(route('contacts.import.store'), ['csv_file' => $file]);

        $this->assertSame(250, $org->contacts()->count());
        $this->assertTrue($org->contacts()->where('email', 'fits@example.com')->exists());
        $this->assertFalse($org->contacts()->where('email', 'overflow@example.com')->exists());
    }

    public function test_inviting_beyond_the_team_member_limit_is_blocked(): void
    {
        [$owner, $org] = $this->makeTenant();
        $org->update(['plan' => 'free']); // limit: 3 team members
        $this->attachMember($org, 'editor');
        $this->attachMember($org, 'viewer'); // owner + these 2 = 3, at the limit

        $this->actingAs($owner)->post(route('team.invite'), [
            'email' => 'new@example.com',
            'role' => 'viewer',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('organization_invitations', ['email' => 'new@example.com']);
    }

    public function test_adding_a_sending_identity_beyond_the_limit_is_blocked(): void
    {
        [$owner, $org] = $this->makeTenant();
        $org->update(['plan' => 'free']); // limit: 1 sending identity
        $this->makeIdentity($org);

        $this->actingAs($owner)->post(route('sending-identities.store'), [
            'from_name' => 'Second', 'from_email' => 'second@example.com',
        ])->assertSessionHasErrors('error');

        $this->assertSame(1, $org->sendingIdentities()->count());
    }

    public function test_dispatch_is_blocked_when_it_would_exceed_the_monthly_email_limit(): void
    {
        [$user, $org] = $this->makeTenant();
        $org->update(['plan' => 'free']); // limit: 500 emails/mo
        $this->useFakeProvider();

        $identity = $this->makeIdentity($org);
        $contacts = [];
        for ($i = 0; $i < 5; $i++) {
            $contacts[] = $this->makeContact($org);
        }
        $campaign = $this->makeCampaign($org, $identity, $contacts);

        // Pretend 498 emails were already sent this month.
        $filler = $this->makeCampaign($org, $identity, [$this->makeContact($org)]);
        \App\Models\CampaignRecipient::where('campaign_id', $filler->id)->update([
            'status' => 'sent', 'sent_at' => now(),
        ]);
        // campaign_recipients has a unique (campaign_id, contact_id) constraint, so each
        // filler row needs its own distinct contact within this one filler campaign.
        for ($i = 0; $i < 497; $i++) {
            \App\Models\CampaignRecipient::create([
                'campaign_id' => $filler->id,
                'contact_id' => $this->makeContact($org)->id,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }

        $this->actingAs($user)
            ->post(route('campaigns.dispatch', $campaign->id))
            ->assertSessionHasErrors('error');

        $this->assertSame('draft', $campaign->fresh()->status);
    }

    public function test_an_unlimited_plan_never_blocks(): void
    {
        [$owner, $org] = $this->makeTenant();
        $org->update(['plan' => 'pro']);

        $planLimits = app(PlanLimitService::class);

        $this->assertTrue($planLimits->canAddContacts($org, 1000000));
        $this->assertTrue($planLimits->canSendEmails($org, 1000000));
    }

    public function test_billing_page_shows_usage_and_owner_can_switch_plans(): void
    {
        [$owner, $org] = $this->makeTenant();
        $this->makeContact($org);

        $this->actingAs($owner)->get(route('billing.index'))->assertOk();

        $this->actingAs($owner)
            ->post(route('billing.upgrade'), ['plan' => 'starter'])
            ->assertRedirect(route('billing.index'));

        $this->assertSame('starter', $org->fresh()->plan);
    }

    public function test_a_manager_cannot_change_the_plan(): void
    {
        [, $org] = $this->makeTenant();
        $manager = $this->attachMember($org, 'manager');

        $this->actingAs($manager)
            ->post(route('billing.upgrade'), ['plan' => 'business'])
            ->assertForbidden();
    }
}
