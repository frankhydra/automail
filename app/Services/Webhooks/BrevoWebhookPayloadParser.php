<?php

namespace App\Services\Webhooks;

/**
 * Turns one Brevo webhook payload into AutoMail's normalized event shape, or
 * null for an event type AutoMail doesn't act on. Pure function - no I/O - so
 * it can be unit tested with plain arrays.
 *
 * Brevo webhook reference: https://developers.brevo.com/docs/transactional-webhooks
 */
class BrevoWebhookPayloadParser
{
    /**
     * @param array<string, mixed> $payload
     * @return array{type: string, message_id: ?string, email: ?string}|null
     */
    public static function parse(array $payload): ?array
    {
        $event = strtolower((string) ($payload['event'] ?? ''));
        $messageId = $payload['message-id'] ?? $payload['message_id'] ?? null;
        $email = $payload['email'] ?? null;

        $type = match ($event) {
            'delivered' => 'delivered',
            // "blocked" and "invalid_email" are permanent rejections, same as a hard bounce.
            'hard_bounce', 'blocked', 'invalid_email' => 'bounced',
            'spam' => 'complained',
            'unsubscribed' => 'unsubscribed',
            // soft_bounce is transient (mailbox full, etc.) - don't suppress on it.
            default => null,
        };

        if ($type === null) {
            return null;
        }

        return ['type' => $type, 'message_id' => $messageId ? (string) $messageId : null, 'email' => $email];
    }
}
