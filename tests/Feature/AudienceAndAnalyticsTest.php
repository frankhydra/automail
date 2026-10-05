<?php

namespace Tests\Feature;

use App\Models\LinkClick;
use App\Models\SuppressionList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class AudienceAndAnalyticsTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_audience_page_loads_and_filters_by_exact_tag(): void
    {
        [$user, $org] = $this->makeTenant();
        $this->makeContact($org, ['first_name' => 'Vera', 'tags' => 'VIP, newsletter']);
        $this->makeContact($org, ['first_name' => 'Nina', 'tags' => 'non-vip']);

        $response = $this->actingAs($user)->get(route('contacts.index', ['tag' => 'vip']));

        $response->assertOk()->assertSee('Vera')->assertDontSee('Nina');
    }

    public function test_audience_never_shows_another_organizations_contacts(): void
    {
        [$user, $org] = $this->makeTenant('Acme');
        [, $other] = $this->makeTenant('Other');
        $this->makeContact($other, ['first_name' => 'Secretive']);

        $this->actingAs($user)->get(route('contacts.index'))->assertOk()->assertDontSee('Secretive');
    }

    public function test_a_manager_can_add_a_contact(): void
    {
        [$user, $org] = $this->makeTenant();

        $this->actingAs($user)->post(route('contacts.store'), [
            'first_name' => 'Sam',
            'email' => ' Sam@Example.com ',
            'tags' => 'lead',
        ])->assertSessionHasNoErrors();

        $contact = $org->contacts()->first();
        $this->assertSame('sam@example.com', $contact->email);
        $this->assertSame('subscribed', $contact->status);
    }

    public function test_a_viewer_cannot_add_or_export_contacts(): void
    {
        [, $org] = $this->makeTenant();
        $viewer = $this->attachMember($org, 'viewer');

        $this->actingAs($viewer)->post(route('contacts.store'), ['email' => 'x@example.com'])->assertForbidden();
        $this->actingAs($viewer)->get(route('contacts.export'))->assertForbidden();
    }

    public function test_adding_a_suppressed_address_is_refused(): void
    {
        [$user, $org] = $this->makeTenant();
        SuppressionList::create(['organization_id' => $org->id, 'email' => 'gone@example.com', 'reason' => 'unsubscribed']);

        $this->actingAs($user)->post(route('contacts.store'), ['email' => 'gone@example.com'])
            ->assertSessionHasErrors('email');

        $this->assertSame(0, $org->contacts()->count());
    }

    public function test_adding_a_duplicate_contact_is_refused(): void
    {
        [$user, $org] = $this->makeTenant();
        $this->makeContact($org, ['email' => 'dup@example.com']);

        $this->actingAs($user)->post(route('contacts.store'), ['email' => 'dup@example.com'])
            ->assertSessionHasErrors('email');
    }

    public function test_export_only_contains_own_contacts_and_neutralises_formulas(): void
    {
        [$user, $org] = $this->makeTenant('Acme');
        [, $other] = $this->makeTenant('Other');
        $this->makeContact($org, ['first_name' => '=HYPERLINK("x")', 'email' => 'mine@example.com']);
        $this->makeContact($other, ['email' => 'theirs@example.com']);

        $csv = $this->actingAs($user)->get(route('contacts.export'))->assertOk()->streamedContent();

        $this->assertStringContainsString('mine@example.com', $csv);
        $this->assertStringNotContainsString('theirs@example.com', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_analytics_page_loads_with_no_campaigns(): void
    {
        [$user] = $this->makeTenant();

        $this->actingAs($user)->get(route('analytics.index'))->assertOk()->assertSee('No sent campaigns yet');
    }

    public function test_analytics_ignores_another_organizations_campaign_id(): void
    {
        [$user, $org] = $this->makeTenant('Acme');
        [, $other] = $this->makeTenant('Other');
        $campaign = $this->makeCampaign($other, $this->makeIdentity($other), [$this->makeContact($other)], ['name' => 'Rival launch', 'status' => 'sent']);

        $this->actingAs($user)->get(route('analytics.index', ['campaign' => $campaign->id]))
            ->assertOk()->assertDontSee('Rival launch');
    }

    public function test_a_tracked_click_is_ranked_in_top_links(): void
    {
        [$user, $org] = $this->makeTenant();
        $contact = $this->makeContact($org);
        $campaign = $this->makeCampaign($org, $this->makeIdentity($org), [$contact], ['name' => 'Spring', 'status' => 'sent']);
        $recipient = $campaign->recipients()->first();
        $recipient->update(['status' => 'sent', 'sent_at' => now()->subHour()]);

        $this->get(URL::signedRoute('track.click', ['recipientId' => $recipient->id, 'url' => 'https://example.com/offer']))
            ->assertRedirect('https://example.com/offer');

        $this->assertSame(1, LinkClick::where('campaign_id', $campaign->id)->count());

        $this->actingAs($user)->get(route('analytics.index', ['campaign' => $campaign->id]))
            ->assertOk()->assertSee('https://example.com/offer');
    }

    public function test_an_open_records_the_mail_app(): void
    {
        [, $org] = $this->makeTenant();
        $campaign = $this->makeCampaign($org, $this->makeIdentity($org), [$this->makeContact($org)], ['status' => 'sent']);
        $recipient = $campaign->recipients()->first();
        $recipient->update(['status' => 'sent', 'sent_at' => now()]);

        $this->withHeader('User-Agent', 'GoogleImageProxy')
            ->get(URL::signedRoute('track.open', ['recipientId' => $recipient->id]))
            ->assertOk();

        $this->assertSame('Gmail', $recipient->fresh()->open_client);
    }
}
