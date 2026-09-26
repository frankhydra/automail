<?php

namespace App\Services\EmailProviders;

use App\Contracts\EmailProviderInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Sends through Brevo's transactional email API (https://api.brevo.com/v3/smtp/email).
 *
 * Requires BREVO_API_KEY in .env. The sender address must also be a verified
 * sender/domain inside your Brevo account, in addition to AutoMail's own
 * verification - Brevo enforces its own sender rules independently of ours.
 */
class BrevoEmailProvider implements EmailProviderInterface
{
    public function __construct(private readonly string $apiKey)
    {
        if ($this->apiKey === '') {
            throw new RuntimeException(
                'EMAIL_PROVIDER=brevo but BREVO_API_KEY is not set. Add it to your .env file.'
            );
        }
    }

    public function send(
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $subject,
        string $bodyHtml,
        ?string $replyTo = null
    ): array {
        $payload = [
            'sender' => ['name' => $fromName, 'email' => $fromEmail],
            'to' => [['email' => $toEmail]],
            'subject' => $subject,
            'htmlContent' => $bodyHtml,
        ];

        if ($replyTo) {
            $payload['replyTo'] = ['email' => $replyTo];
        }

        try {
            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
                'accept' => 'application/json',
            ])->post('https://api.brevo.com/v3/smtp/email', $payload);
        } catch (Throwable $e) {
            return ['success' => false, 'message_id' => null, 'error' => 'Brevo request failed: '.$e->getMessage()];
        }

        if ($response->successful()) {
            return [
                'success' => true,
                'message_id' => $response->json('messageId'),
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'message_id' => null,
            'error' => 'Brevo error ('.$response->status().'): '.($response->json('message') ?? $response->body()),
        ];
    }
}
