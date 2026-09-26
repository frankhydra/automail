<?php

namespace Tests\Unit;

use App\Services\Webhooks\BrevoWebhookPayloadParser;
use App\Services\Webhooks\ResendWebhookPayloadParser;
use Tests\TestCase;

class WebhookPayloadParserTest extends TestCase
{
    public function test_brevo_parser_maps_known_events(): void
    {
        $this->assertSame('bounced', BrevoWebhookPayloadParser::parse(['event' => 'hard_bounce'])['type']);
        $this->assertSame('bounced', BrevoWebhookPayloadParser::parse(['event' => 'blocked'])['type']);
        $this->assertSame('complained', BrevoWebhookPayloadParser::parse(['event' => 'spam'])['type']);
        $this->assertSame('delivered', BrevoWebhookPayloadParser::parse(['event' => 'delivered'])['type']);
        $this->assertNull(BrevoWebhookPayloadParser::parse(['event' => 'soft_bounce']));
        $this->assertNull(BrevoWebhookPayloadParser::parse(['event' => 'opened']));
    }

    public function test_resend_parser_maps_known_events_and_extracts_email(): void
    {
        $result = ResendWebhookPayloadParser::parse([
            'type' => 'email.bounced',
            'data' => ['email_id' => 'abc123', 'to' => ['someone@example.com']],
        ]);

        $this->assertSame('bounced', $result['type']);
        $this->assertSame('abc123', $result['message_id']);
        $this->assertSame('someone@example.com', $result['email']);

        $this->assertNull(ResendWebhookPayloadParser::parse(['type' => 'email.opened', 'data' => []]));
    }
}
