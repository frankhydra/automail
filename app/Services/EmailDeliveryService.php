<?php

namespace App\Services;

use App\Contracts\EmailProviderInterface;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Integration;
use App\Support\UtmTagger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

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
     * Render personalized merge variables, add the unsubscribe footer and tracking,
     * and dispatch the email for a single recipient.
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

        // The subject is a plain-text header: do not HTML-escape it, but strip line
        // breaks so a contact value can never inject extra email headers.
        $renderedSubject = preg_replace(
            '/[\r\n]+/',
            ' ',
            $this->renderer->render($campaign->subject, $contact, false)
        );

        // The body is HTML: contact values are HTML-escaped (default).
        $renderedBody = $this->renderer->render($campaign->body, $contact);

        $renderedBody = $this->appendUnsubscribeFooter($renderedBody, $recipient->id);
        $renderedBody = $this->injectTracking($renderedBody, $recipient->id, $this->utmFor($campaign));

        $result = $this->provider->send(
            $identity->from_email,
            $identity->from_name,
            $contact->email,
            $renderedSubject,
            $renderedBody,
            $identity->reply_to
        );

        if (!empty($result['success'])) {
            $recipient->update([
                'status' => 'sent',
                'error_message' => null,
                'sent_at' => now(),
                'provider_message_id' => $result['message_id'] ?? null,
            ]);
            return true;
        }

        $recipient->update([
            'status' => 'failed',
            'error_message' => $result['error'] ?? 'Unknown delivery failure',
        ]);
        return false;
    }

    /**
     * Add a signed, per-recipient unsubscribe link to every campaign email.
     */
    protected function appendUnsubscribeFooter(string $htmlBody, int $recipientId): string
    {
        $url = URL::signedRoute('unsubscribe.show', ['recipient' => $recipientId]);

        $footer = '<p style="margin-top:24px;font-size:12px;color:#6b7280;font-family:Arial,sans-serif;">'
            .'You are receiving this email because you subscribed to our mailing list. '
            .'<a href="'.e($url).'">Unsubscribe</a></p>';

        return $this->insertBeforeBodyEnd($htmlBody, $footer);
    }

    /**
     * Add the open-tracking pixel and rewrite http(s) links for click tracking.
     * Tracking URLs are signed, so they cannot be forged or used as open redirects.
     */
    protected function injectTracking(string $htmlBody, int $recipientId, array $utm = []): string
    {
        // Rewrite links first, so the pixel added afterwards is not touched.
        $htmlBody = preg_replace_callback(
            '/href=(["\'])(.*?)\1/is',
            function (array $matches) use ($recipientId, $utm) {
                $originalUrl = html_entity_decode($matches[2], ENT_QUOTES);

                // Only track absolute http(s) links; leave mailto:, tel:, #anchors and the unsubscribe link alone.
                if (!preg_match('#^https?://#i', $originalUrl) || str_contains($originalUrl, '/unsubscribe/')) {
                    return $matches[0];
                }

                // Google Analytics tags are added BEFORE the link is wrapped for click
                // tracking, so the visitor lands on the tagged address.
                if ($utm !== [] && UtmTagger::hostAllowed($originalUrl, $utm['domains'])) {
                    $originalUrl = UtmTagger::apply($originalUrl, $utm['params']);
                }

                $trackedUrl = URL::signedRoute('track.click', [
                    'recipientId' => $recipientId,
                    'url' => $originalUrl,
                ]);

                return 'href="'.e($trackedUrl).'"';
            },
            $htmlBody
        );

        $pixelUrl = URL::signedRoute('track.open', ['recipientId' => $recipientId]);
        $pixelTag = '<img src="'.e($pixelUrl).'" width="1" height="1" alt="" style="display:none;" />';

        return $this->insertBeforeBodyEnd($htmlBody, $pixelTag);
    }

    /**
     * The organization's Google Analytics (UTM) settings for this campaign, or [] when
     * the integration is off. Cached briefly because this runs once per email sent.
     *
     * @return array{params: array<string, string>, domains: list<string>}|array{}
     */
    protected function utmFor(Campaign $campaign): array
    {
        $settings = Cache::remember(
            'integration:utm:'.$campaign->organization_id,
            60,
            function () use ($campaign) {
                $integration = Integration::forOrganization($campaign->organization_id, 'utm');

                return $integration && $integration->enabled ? (array) $integration->settings : [];
            }
        );

        if ($settings === []) {
            return [];
        }

        return [
            'params' => [
                'utm_source' => (string) ($settings['source'] ?? 'automail'),
                'utm_medium' => (string) ($settings['medium'] ?? 'email'),
                'utm_campaign' => Str::limit(Str::slug($campaign->name), 60, '') ?: 'campaign',
            ],
            'domains' => array_values((array) ($settings['domains'] ?? [])),
        ];
    }

    protected function insertBeforeBodyEnd(string $htmlBody, string $snippet): string
    {
        $position = stripos($htmlBody, '</body>');

        if ($position !== false) {
            return substr($htmlBody, 0, $position).$snippet.substr($htmlBody, $position);
        }

        return $htmlBody.$snippet;
    }
}
