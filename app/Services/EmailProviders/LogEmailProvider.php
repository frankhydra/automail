<?php

namespace App\Services\EmailProviders;

use App\Contracts\EmailProviderInterface;
use Illuminate\Support\Facades\Log;

class LogEmailProvider implements EmailProviderInterface
{
    /**
     * Simulate sending an email by writing delivery contents to laravel.log.
     */
    public function send(
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $subject,
        string $bodyHtml,
        ?string $replyTo = null
    ): array {
        Log::info("=== AUTOMAIL LOG EMAIL DELIVERY SERVICE ===");
        Log::info("FROM: {$fromName} <{$fromEmail}>");
        if ($replyTo) {
            Log::info("REPLY-TO: {$replyTo}");
        }
        Log::info("TO: {$toEmail}");
        Log::info("SUBJECT: {$subject}");
        Log::info("BODY HTML:\n{$bodyHtml}");
        Log::info("===========================================");

        return [
            'success' => true,
            'message_id' => 'log-' . uniqid(),
            'error' => null,
        ];
    }
}