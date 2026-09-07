<?php

namespace App\Services;

use App\Contracts\EmailProviderInterface;
use App\Models\Campaign;
use App\Models\CampaignRecipient;

class EmailDeliveryService
{
    protected EmailProviderInterface $provider;
    protected TemplateRendererService $renderer;

    public function __construct(EmailProviderInterface $provider, TemplateRendererService $renderer)
    {
        $this->provider = $provider;
        $this->renderer = $renderer;
    }

    /**
     * Render personalized merge variables and dispatch email for a single recipient.
     */
    public function sendRecipientEmail(Campaign $campaign, CampaignRecipient $recipient): bool
    {
        $contact = $recipient->contact;
        $identity = $campaign->sendingIdentity;

        if (!$contact || !$identity) {
            $recipient->update([
                'status' => 'failed',
                'error_message' => 'Missing contact or sending identity reference.',
            ]);
            return false;
        }

        // Render personalized subject and body
        $renderedSubject = $this->renderer->render($campaign->subject, $contact);
        $renderedBody = $this->renderer->render($campaign->body, $contact);

        $result = $this->provider->send(
            $identity->from_email,
            $identity->from_name,
            $contact->email,
            $renderedSubject,
            $renderedBody,
            $identity->reply_to
        );

        if ($result['success']) {
            $recipient->update([
                'status' => 'sent',
                'error_message' => null,
                'sent_at' => now(),
            ]);
            return true;
        } else {
            $recipient->update([
                'status' => 'failed',
                'error_message' => $result['error'] ?? 'Unknown delivery failure',
            ]);
            return false;
        }
    }
}