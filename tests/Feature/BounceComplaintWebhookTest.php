<?php

namespace Tests\Feature;

use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class BounceComplaintWebhookTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    protected function signedResendHeaders(string $body, string $secret, ?string $timestamp = null): array
    {
        $id = 'msg_test123';
        $timestamp = $timestamp ?? (string) time();
        $secretBytes = base64_decode(preg_replace('/^whsec_/', '', $secret));
        $signature = base64_encode(hash_hmac('sha256', "{$id}.{$timestamp}.{$body}", $secretBytes, true));

        return [
            'svix-id' => $id,
            'svix-timestamp' => $timestamp,
            'svix-signature' => "v1,{$signature}",
        ];
    }

    // --- Brevo ---

    public function test_brevo_bounce_suppresses_the_contact_and_marks_the_recipient(): void
    {
        Config::set('services.brevo.webhook_token', 'test-token');

        [, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org);
        $contact = $this->makeContact($org, ['email' => 'bounced@example.com']);
        $campaign = $this->makeCampaign($org, $identity, [$contact]);
        $recipient = $campaign->recipients()->first();
        $recipient->update(['provider_message_id' => 'brevo-msg-1', 'status' => 'sent']);

        $this->postJson('/webhooks/brevo/test-token', [
            'event' => 'hard_bounce',
            'email' => 'bounced@example.com',
            'message-id' => 'brevo-msg-1',
        ])->assertNoContent();

        $this->assertSame('bounced', $recipient->fresh()->status);
        $this->assertNotNull($recipient->fresh()->bounced_at);
        $this->assertSame('bounced', $contact->fresh()->status);
        $this->assertDatabaseHas('suppression_lists', [
            'organization_id' => $org->id,
            'email' => 'bounced@example.com',
            'reason' => 'bounced',
        ]);
    }

    public function test_brevo_spam_complaint_unsubscribes_the_contact(): void
    {
        Config::set('services.brevo.webhook_token', 'test-token');

        [, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org);
        $contact = $this->makeContact($org, ['email' => 'angry@example.com']);
        $campaign = $this->makeCampaign($org, $identity, [$contact]);
        $recipient = $campaign->recipients()->first();
        $recipient->update(['provider_message_id' => 'brevo-msg-2', 'status' => 'sent']);

        $this->postJson('/webhooks/brevo/test-token', [
            'event' => 'spam',
            'email' => 'angry@example.com',
            'message-id' => 'brevo-msg-2',
        ])->assertNoContent();

        $this->assertSame('complained', $recipient->fresh()->status);
        $this->assertSame('unsubscribed', $contact->fresh()->status);
        $this->assertDatabaseHas('suppression_lists', ['email' => 'angry@example.com', 'reason' => 'complained']);
    }

    public function test_brevo_soft_bounce_is_ignored_and_does_not_suppress(): void
    {
        Config::set('services.brevo.webhook_token', 'test-token');

        [, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org);
        $contact = $this->makeContact($org, ['email' => 'temporary@example.com']);
        $campaign = $this->makeCampaign($org, $identity, [$contact]);
        $recipient = $campaign->recipients()->first();
        $recipient->update(['provider_message_id' => 'brevo-msg-3', 'status' => 'sent']);

        $this->postJson('/webhooks/brevo/test-token', [
            'event' => 'soft_bounce',
            'email' => 'temporary@example.com',
            'message-id' => 'brevo-msg-3',
        ])->assertNoContent();

        $this->assertSame('sent', $recipient->fresh()->status);
        $this->assertSame('subscribed', $contact->fresh()->status);
        $this->assertDatabaseMissing('suppression_lists', ['email' => 'temporary@example.com']);
    }

    public function test_brevo_webhook_rejects_a_wrong_token(): void
    {
        Config::set('services.brevo.webhook_token', 'test-token');

        $this->postJson('/webhooks/brevo/wrong-token', ['event' => 'hard_bounce'])
            ->assertForbidden();
    }

    // --- Resend ---

    public function test_resend_bounce_is_verified_and_suppresses_the_contact(): void
    {
        Config::set('services.resend.webhook_secret', 'whsec_dGVzdHNlY3JldGZvcnJlc2VuZA==');

        [, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org);
        $contact = $this->makeContact($org, ['email' => 'bounced-resend@example.com']);
        $campaign = $this->makeCampaign($org, $identity, [$contact]);
        $recipient = $campaign->recipients()->first();
        $recipient->update(['provider_message_id' => 'resend-msg-1', 'status' => 'sent']);

        $body = json_encode([
            'type' => 'email.bounced',
            'data' => ['email_id' => 'resend-msg-1', 'to' => ['bounced-resend@example.com']],
        ]);

        $headers = $this->signedResendHeaders($body, 'whsec_dGVzdHNlY3JldGZvcnJlc2VuZA==');

        $this->call('POST', '/webhooks/resend', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertNoContent();

        $this->assertSame('bounced', $recipient->fresh()->status);
        $this->assertSame('bounced', $contact->fresh()->status);
        $this->assertDatabaseHas('suppression_lists', ['email' => 'bounced-resend@example.com', 'reason' => 'bounced']);
    }

    public function test_resend_webhook_rejects_a_bad_signature(): void
    {
        Config::set('services.resend.webhook_secret', 'whsec_dGVzdHNlY3JldGZvcnJlc2VuZA==');

        $body = json_encode(['type' => 'email.bounced', 'data' => ['email_id' => 'x']]);
        $headers = [
            'svix-id' => 'msg_test123',
            'svix-timestamp' => (string) time(),
            'svix-signature' => 'v1,not-a-real-signature',
        ];

        $this->call('POST', '/webhooks/resend', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertForbidden();
    }

    public function test_resend_webhook_rejects_an_old_timestamp_to_block_replay(): void
    {
        Config::set('services.resend.webhook_secret', 'whsec_dGVzdHNlY3JldGZvcnJlc2VuZA==');

        $body = json_encode(['type' => 'email.bounced', 'data' => ['email_id' => 'x']]);
        $oldTimestamp = (string) (time() - 3600);
        $headers = $this->signedResendHeaders($body, 'whsec_dGVzdHNlY3JldGZvcnJlc2VuZA==', $oldTimestamp);

        $this->call('POST', '/webhooks/resend', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertForbidden();
    }

    // --- Cross-cutting behavior ---

    public function test_an_unmatched_event_is_logged_but_touches_no_contact(): void
    {
        Config::set('services.brevo.webhook_token', 'test-token');

        $this->postJson('/webhooks/brevo/test-token', [
            'event' => 'hard_bounce',
            'email' => 'unknown@example.com',
            'message-id' => 'no-such-message-id',
        ])->assertNoContent();

        $this->assertDatabaseHas('webhook_events', ['provider' => 'brevo', 'event_type' => 'bounced']);
        $this->assertDatabaseMissing('suppression_lists', ['email' => 'unknown@example.com']);
    }

    public function test_the_same_event_delivered_twice_is_only_processed_once(): void
    {
        Config::set('services.brevo.webhook_token', 'test-token');

        [, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org);
        $contact = $this->makeContact($org, ['email' => 'dup@example.com']);
        $campaign = $this->makeCampaign($org, $identity, [$contact]);
        $recipient = $campaign->recipients()->first();
        $recipient->update(['provider_message_id' => 'brevo-msg-dup', 'status' => 'sent']);

        $payload = ['event' => 'hard_bounce', 'email' => 'dup@example.com', 'message-id' => 'brevo-msg-dup'];

        $this->postJson('/webhooks/brevo/test-token', $payload)->assertNoContent();
        $this->postJson('/webhooks/brevo/test-token', $payload)->assertNoContent();

        $this->assertSame(1, WebhookEvent::where('dedupe_key', hash('sha256', 'brevo|hard_bounce|brevo-msg-dup'))->count());
    }
}
