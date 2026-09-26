<?php

namespace App\Services\EmailProviders;

use App\Contracts\EmailProviderInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Sends through Resend's API (https://api.resend.com/emails).
 *
 * Requires RESEND_API_KEY in .env. Resend also requires the sending domain to
 * be added and verified inside your Resend account, in addition to AutoMail's
 * own verification.
 */
class ResendEmailProvider implements EmailProviderInterface
{
    public function __construct(private readonly string $apiKey)
    {
        if ($this->apiKey === '') {
            throw new RuntimeException(
                'EMAIL_PROVIDER=resend but RESEND_API_KEY is not set. Add it to your .env file.'
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
            'from' => "{$fromName} <{$fromEmail}>",
            'to' => [$toEmail],
            'subject' => $subject,
            'html' => $bodyHtml,
        ];

        if ($replyTo) {
            $payload['reply_to'] = $replyTo;
        }

        try {
            $response = Http::withToken($this->apiKey)->post('https://api.resend.com/emails', $payload);
        } catch (Throwable $e) {
            return ['success' => false, 'message_id' => null, 'error' => 'Resend request failed: '.$e->getMessage()];
        }

        if ($response->successful()) {
            return [
                'success' => true,
                'message_id' => $response->json('id'),
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'message_id' => null,
            'error' => 'Resend error ('.$response->status().'): '.($response->json('message') ?? $response->body()),
        ];
    }
}
