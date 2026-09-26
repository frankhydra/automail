<?php

namespace Tests\Support;

use App\Contracts\EmailProviderInterface;

class FakeEmailProvider implements EmailProviderInterface
{
    /** @var list<array<string, mixed>> */
    public array $sent = [];

    public function __construct(public bool $succeed = true)
    {
    }

    public function send(
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $subject,
        string $bodyHtml,
        ?string $replyTo = null
    ): array {
        if (!$this->succeed) {
            return ['success' => false, 'message_id' => null, 'error' => 'Provider rejected'];
        }

        $this->sent[] = compact('fromEmail', 'fromName', 'toEmail', 'subject', 'bodyHtml', 'replyTo');

        return ['success' => true, 'message_id' => 'fake-'.count($this->sent), 'error' => null];
    }
}
