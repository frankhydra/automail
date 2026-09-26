<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Services\EmailDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class EmailRenderingSecurityTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_contact_html_is_escaped_in_the_rendered_body(): void
    {
        [, $org] = $this->makeTenant();
        $provider = $this->useFakeProvider();

        $identity = $this->makeIdentity($org);
        $contact = $this->makeContact($org, [
            'first_name' => '<script>alert(1)</script>',
            'email' => 'attacker@example.com',
        ]);

        $campaign = $this->makeCampaign($org, $identity, [$contact], [
            'subject' => 'Hi {{first_name}}',
            'body' => '<p>Hello {{first_name}}</p>',
        ]);

        $recipient = $campaign->recipients()->first();

        app(EmailDeliveryService::class)->sendRecipientEmail($campaign->fresh(), $recipient);

        $this->assertCount(1, $provider->sent);
        $this->assertStringNotContainsString('<script>', $provider->sent[0]['bodyHtml']);
        $this->assertStringContainsString('&lt;script&gt;', $provider->sent[0]['bodyHtml']);

        // The subject is plain text (not HTML-escaped) but header injection must be stripped.
        $contact2 = $this->makeContact($org, ['first_name' => "Line1\r\nBcc: evil@example.com"]);
        $campaign2 = $this->makeCampaign($org, $identity, [$contact2], ['subject' => '{{first_name}}']);
        app(EmailDeliveryService::class)->sendRecipientEmail($campaign2->fresh(), $campaign2->recipients()->first());

        $this->assertStringNotContainsString("\r\n", $provider->sent[1]['subject']);
        $this->assertStringNotContainsString("\n", $provider->sent[1]['subject']);
    }

    public function test_every_sent_email_contains_a_working_unsubscribe_link(): void
    {
        [, $org] = $this->makeTenant();
        $provider = $this->useFakeProvider();

        $identity = $this->makeIdentity($org);
        $contact = $this->makeContact($org);
        $campaign = $this->makeCampaign($org, $identity, [$contact], ['body' => '<html><body><p>Hi</p></body></html>']);
        $recipient = $campaign->recipients()->first();

        app(EmailDeliveryService::class)->sendRecipientEmail($campaign->fresh(), $recipient);

        $html = $provider->sent[0]['bodyHtml'];
        $this->assertMatchesRegularExpression('#href="[^"]*unsubscribe/'.$recipient->id.'\?[^"]*signature=#', $html);

        // Extract the link and confirm it actually works end to end.
        preg_match('/href="([^"]*unsubscribe[^"]*)"/', $html, $m);
        $url = html_entity_decode($m[1]);

        $this->get($url)->assertOk();
        $this->post($url)->assertOk();

        $this->assertSame('unsubscribed', $contact->fresh()->status);
        $this->assertDatabaseHas('suppression_lists', [
            'organization_id' => $org->id,
            'email' => $contact->email,
        ]);
    }

    public function test_unsubscribe_link_cannot_be_tampered_to_affect_another_recipient(): void
    {
        [, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org);
        $victim = $this->makeContact($org);
        $attacker = $this->makeContact($org);
        $campaign = $this->makeCampaign($org, $identity, [$victim, $attacker]);

        $victimRecipient = $campaign->recipients()->where('contact_id', $victim->id)->first();
        $attackerRecipient = $campaign->recipients()->where('contact_id', $attacker->id)->first();

        $validUrl = \Illuminate\Support\Facades\URL::signedRoute('unsubscribe.show', ['recipient' => $attackerRecipient->id]);
        $tamperedUrl = str_replace((string) $attackerRecipient->id, (string) $victimRecipient->id, $validUrl);

        $this->get($tamperedUrl)->assertForbidden();
        $this->assertSame('subscribed', $victim->fresh()->status);
    }
}
