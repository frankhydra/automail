<?php

namespace App\Services\EmailProviders;

use App\Contracts\EmailProviderInterface;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SmtpEmailProvider implements EmailProviderInterface
{
    /**
     * Send an email via standard Laravel SMTP mailer.
     */
    public function send(
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $subject,
        string $bodyHtml,
        ?string $replyTo = null
    ): array {
        try {
            Mail::html($bodyHtml, function ($message) use ($fromEmail, $fromName, $toEmail, $subject, $replyTo) {
                $message->to($toEmail)
                    ->from($fromEmail, $fromName)
                    ->subject($subject);

                if ($replyTo) {
                    $message->replyTo($replyTo);
                }
            });

            return [
                'success' => true,
                'message_id' => 'smtp-' . uniqid(),
                'error' => null,
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}