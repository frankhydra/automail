<?php

namespace App\Contracts;

interface EmailProviderInterface
{
    /**
     * Send a rendered email to a single recipient.
     *
     * @param string $fromEmail
     * @param string $fromName
     * @param string $toEmail
     * @param string $subject
     * @param string $bodyHtml
     * @param string|null $replyTo
     * @return array ['success' => bool, 'message_id' => string|null, 'error' => string|null]
     */
    public function send(
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $subject,
        string $bodyHtml,
        ?string $replyTo = null
    ): array;
}