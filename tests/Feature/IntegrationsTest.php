<?php

namespace Tests\Feature;

use App\Models\Automation;
use App\Models\Integration;
use App\Models\SuppressionList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class IntegrationsTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    /** Creates the intake URL through the page and returns the secret token. */
    protected function connect($user): string
    {
        $this->actingAs($user)->post(route('integrations.webhook.generate'))->assertRedirect(route('integrations.index'));

        $url = session('integration_url');
        $this->assertNotEmpty($url);

        return basename(parse_url($url, PHP_URL_PATH));
    }

    public function test_the_page_loads_for_everyone_in_the_organization(): void
    {
        [$owner, $org] = $this->makeTenant();
        $viewer = $this->attachMember($org, 'viewer');

        $this->actingAs($owner)->get(route('integrations.index'))->assertOk()->assertSee('Integrations');
        $this->actingAs($viewer)->get(route('integrations.index'))->assertOk();
    }

    public function test_only_the_hash_of_the_token_is_stored(): void
    {
        [$owner, $org] = $this->makeTenant();
        $token = $this->connect($owner);

        $row = Integration::forOrganization($org->id, 'webhook');

        $this->assertNotSame($token, $row->secret_hash);
        $this->assertSame(hash('sha256', $token), $row->secret_hash);
    }

    public function test_the_intake_url_adds_a_subscribed_contact_with_tags(): void
    {
        [$owner, $org] = $this->makeTenant();
        $token = $this->connect($owner);
        $this->actingAs($owner)->put(route('integrations.webhook.update'), ['enabled' => 1, 'default_tags' => 'Website']);

        $this->postJson(route('webhooks.contacts', $token), [
            'email' => ' Jane@Example.com ',
            'first_name' => 'Jane',
            'tags' => ['VIP', 'website'],
        ])->assertCreated()->assertJsonPath('status', 'created');

        $contact = $org->contacts()->first();
        $this->assertSame('jane@example.com', $contact->email);
        $this->assertSame('subscribed', $contact->status);
        $this->assertEqualsCanonicalizing(['website', 'vip'], $contact->tagList());
    }

    public function test_a_contact_received_this_way_starts_automations(): void
    {
        [$owner, $org] = $this->makeTenant();
        $token = $this->connect($owner);

        $template = $org->templates()->create(['name' => 'T', 'subject' => 'S', 'body' => '<p>x</p>']);
        $automation = $org->automations()->create(['name' => 'Welcome', 'status' => 'active', 'trigger_type' => 'contact_added']);
        $automation->nodes()->create(['type' => 'email', 'config' => ['template_id' => $template->id]]);

        $this->postJson(route('webhooks.contacts', $token), ['email' => 'new@example.com'])->assertCreated();

        $this->assertSame(1, $automation->runs()->count());
    }

    public function test_sending_the_same_address_twice_is_harmless(): void
    {
        [$owner, $org] = $this->makeTenant();
        $token = $this->connect($owner);

        $this->postJson(route('webhooks.contacts', $token), ['email' => 'a@example.com'])->assertCreated();
        $this->postJson(route('webhooks.contacts', $token), ['email' => 'a@example.com'])->assertOk()->assertJsonPath('status', 'exists');

        $this->assertSame(1, $org->contacts()->count());
    }

    public function test_a_suppressed_address_is_not_added_back(): void
    {
        [$owner, $org] = $this->makeTenant();
        $token = $this->connect($owner);
        SuppressionList::create(['organization_id' => $org->id, 'email' => 'gone@example.com', 'reason' => 'unsubscribed']);

        $this->postJson(route('webhooks.contacts', $token), ['email' => 'gone@example.com'])
            ->assertOk()->assertJsonPath('status', 'skipped');

        $this->assertSame(0, $org->contacts()->count());
    }

    public function test_invalid_data_is_rejected(): void
    {
        [$owner] = $this->makeTenant();
        $token = $this->connect($owner);

        $this->postJson(route('webhooks.contacts', $token), ['email' => 'not-an-email'])->assertStatus(422);
        $this->postJson(route('webhooks.contacts', $token), [])->assertStatus(422);
    }

    public function test_a_wrong_or_switched_off_token_gets_nothing(): void
    {
        [$owner, $org] = $this->makeTenant();
        $token = $this->connect($owner);

        $this->postJson(route('webhooks.contacts', 'x'.$token), ['email' => 'a@example.com'])->assertNotFound();

        $this->actingAs($owner)->put(route('integrations.webhook.update'), ['default_tags' => '']);
        $this->postJson(route('webhooks.contacts', $token), ['email' => 'a@example.com'])->assertNotFound();

        $this->assertSame(0, $org->contacts()->count());
    }

    public function test_creating_a_new_url_revokes_the_old_one(): void
    {
        [$owner, $org] = $this->makeTenant();
        $old = $this->connect($owner);
        $new = $this->connect($owner);

        $this->postJson(route('webhooks.contacts', $old), ['email' => 'a@example.com'])->assertNotFound();
        $this->postJson(route('webhooks.contacts', $new), ['email' => 'a@example.com'])->assertCreated();
    }

    public function test_a_token_only_ever_adds_to_its_own_organization(): void
    {
        [$ownerA, $orgA] = $this->makeTenant('A');
        [, $orgB] = $this->makeTenant('B');
        $token = $this->connect($ownerA);

        $this->postJson(route('webhooks.contacts', $token), ['email' => 'a@example.com'])->assertCreated();

        $this->assertSame(1, $orgA->contacts()->count());
        $this->assertSame(0, $orgB->contacts()->count());
    }

    public function test_the_contact_limit_of_the_plan_is_respected(): void
    {
        [$owner, $org] = $this->makeTenant();
        $token = $this->connect($owner);

        $rows = [];
        for ($i = 0; $i < 250; $i++) {
            $rows[] = ['organization_id' => $org->id, 'email' => "c{$i}@example.com", 'status' => 'subscribed', 'created_at' => now(), 'updated_at' => now()];
        }
        \App\Models\Contact::insert($rows); // bulk insert: no model events, as on the free-plan limit

        $this->postJson(route('webhooks.contacts', $token), ['email' => 'one-too-many@example.com'])->assertStatus(422);
    }

    public function test_only_owners_and_admins_can_change_integrations(): void
    {
        [$owner, $org] = $this->makeTenant();
        $manager = $this->attachMember($org, 'manager');

        $this->actingAs($manager)->post(route('integrations.webhook.generate'))->assertForbidden();
        $this->actingAs($manager)->put(route('integrations.utm.update'), ['enabled' => 1, 'source' => 'a', 'medium' => 'b'])->assertForbidden();
        $this->assertSame(0, Integration::count());
    }

    public function test_utm_settings_are_validated_and_saved(): void
    {
        [$owner, $org] = $this->makeTenant();

        $this->actingAs($owner)->put(route('integrations.utm.update'), ['enabled' => 1, 'source' => 'bad value!', 'medium' => 'email'])
            ->assertSessionHasErrors('source');

        $this->actingAs($owner)->put(route('integrations.utm.update'), [
            'enabled' => 1, 'source' => 'automail', 'medium' => 'email', 'domains' => 'https://Shop.com/, junk, blog.shop.com',
        ])->assertSessionHasNoErrors();

        $row = Integration::forOrganization($org->id, 'utm');
        $this->assertTrue($row->enabled);
        $this->assertSame(['shop.com', 'blog.shop.com'], $row->settings['domains']);
    }

    protected function sentBodyFor($org, string $body): string
    {
        $fake = $this->useFakeProvider();
        $contact = $this->makeContact($org);
        $campaign = $this->makeCampaign($org, $this->makeIdentity($org), [$contact], ['name' => 'Spring Sale!', 'status' => 'sending', 'body' => $body]);

        app(\App\Services\EmailDeliveryService::class)->sendRecipientEmail($campaign, $campaign->recipients()->first());

        return html_entity_decode($fake->sent[0]['bodyHtml'] ?? '', ENT_QUOTES);
    }

    public function test_links_in_sent_emails_get_utm_tags_that_survive_click_tracking(): void
    {
        [$owner, $org] = $this->makeTenant();
        $this->actingAs($owner)->put(route('integrations.utm.update'), ['enabled' => 1, 'source' => 'automail', 'medium' => 'email']);
        Cache::flush();

        $sent = $this->sentBodyFor($org, '<a href="https://shop.com/sale">Shop</a> <a href="https://shop.com/x?utm_source=partner">Keep</a>');

        preg_match_all('#url=([^&"]+)#', $sent, $m);
        $urls = array_map('urldecode', $m[1]);

        $this->assertContains('https://shop.com/sale?utm_source=automail&utm_medium=email&utm_campaign=spring-sale', $urls);
        $this->assertContains('https://shop.com/x?utm_source=partner&utm_medium=email&utm_campaign=spring-sale', $urls);
    }

    public function test_the_domain_filter_and_the_off_switch_are_respected(): void
    {
        [$owner, $org] = $this->makeTenant();
        $this->actingAs($owner)->put(route('integrations.utm.update'), ['enabled' => 1, 'source' => 'automail', 'medium' => 'email', 'domains' => 'shop.com']);
        Cache::flush();

        $sent = $this->sentBodyFor($org, '<a href="https://shop.com/a">Mine</a> <a href="https://twitter.com/b">Other</a>');

        // The real destination sits URL-encoded inside the click-tracking link, so decode it first.
        preg_match_all('#url=([^&"]+)#', $sent, $m);
        $urls = array_map('urldecode', $m[1]);

        $this->assertContains('https://shop.com/a?utm_source=automail&utm_medium=email&utm_campaign=spring-sale', $urls);
        $this->assertContains('https://twitter.com/b', $urls); // not on the allowed-domain list: left alone

        [$owner2, $org2] = $this->makeTenant('Two');
        $this->actingAs($owner2)->put(route('integrations.utm.update'), ['enabled' => 0, 'source' => 'automail', 'medium' => 'email']);
        Cache::flush();

        $this->assertStringNotContainsString('utm_source', $this->sentBodyFor($org2, '<a href="https://shop.com/a">Mine</a>'));
    }
}
