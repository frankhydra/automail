<?php

namespace App\Services\Webhooks;

/**
 * Turns one Resend webhook payload into AutoMail's normalized event shape, or
 * null for an event type AutoMail doesn't act on. Pure function - no I/O - so
 * it can be unit tested with plain arrays.
 *
 * Resend webhook reference: https://resend.com/docs/dashboard/webhooks/event-types
 */
class ResendWebhookPayloadParser
{
    /**
     * @param array<string, mixed> $payload
     * @return array{type: string, message_id: ?string, email: ?string}|null
     */
    public static function parse(array $payload): ?array
    {
        $event = strtolower((string) ($payload['type'] ?? ''));
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        $messageId = $data['email_id'] ?? null;
        $to = $data['to'] ?? null;
        $email = is_array($to) ? ($to[0] ?? null) : $to;

        $type = match ($event) {
            'email.delivered' => 'delivered',
            'email.bounced' => 'bounced',
            'email.complained' => 'complained',
            default => null,
        };

        if ($type === null) {
            return null;
        }

        return ['type' => $type, 'message_id' => $messageId ? (string) $messageId : null, 'email' => $email];
    }
}
