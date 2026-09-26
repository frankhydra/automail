<?php

namespace App\Http\Controllers;

use App\Services\Webhooks\BrevoWebhookPayloadParser;
use App\Services\Webhooks\ResendWebhookPayloadParser;
use App\Services\Webhooks\WebhookEventProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Public endpoints that Brevo/Resend call to report bounces, complaints, and
 * deliveries after a send. Both are unauthenticated by definition (the
 * provider isn't logged into AutoMail), so each verifies the request its own
 * way instead of relying on session/CSRF - see the two verify* methods.
 */
class WebhookController extends Controller
{
    public function __construct(private readonly WebhookEventProcessor $processor)
    {
    }

    /**
     * Brevo does not sign webhooks on standard plans, so the shared secret is
     * embedded in the URL itself. Generate one long random value and configure
     * it as the webhook URL in your Brevo account: BREVO_WEBHOOK_TOKEN in .env.
     */
    public function brevo(Request $request, string $token): Response
    {
        $expected = (string) config('services.brevo.webhook_token');

        if ($expected === '' || !hash_equals($expected, $token)) {
            abort(403, 'Invalid webhook token.');
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        $event = BrevoWebhookPayloadParser::parse($payload);
        $dedupeKey = $this->dedupeKey('brevo', $payload, $payload['event'] ?? '', $payload['message-id'] ?? null);

        $this->processor->process('brevo', $event, $dedupeKey, $payload);

        return response()->noContent();
    }

    /**
     * Resend signs every webhook using the Svix standard (HMAC-SHA256).
     * RESEND_WEBHOOK_SECRET comes from your Resend webhook's "Signing Secret".
     */
    public function resend(Request $request): Response
    {
        $secret = (string) config('services.resend.webhook_secret');

        if ($secret === '' || !$this->verifyResendSignature($request, $secret)) {
            abort(403, 'Invalid webhook signature.');
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        $event = ResendWebhookPayloadParser::parse($payload);
        $dedupeKey = $this->dedupeKey('resend', $payload, $payload['type'] ?? '', $payload['data']['email_id'] ?? null);

        $this->processor->process('resend', $event, $dedupeKey, $payload);

        return response()->noContent();
    }

    /**
     * Prefer a natural identifier from the provider so a genuine retry of the
     * same event is recognized as a duplicate; fall back to hashing the whole
     * payload if none is present.
     *
     * @param array<string, mixed> $payload
     */
    protected function dedupeKey(string $provider, array $payload, string $eventName, ?string $naturalId): string
    {
        $basis = $naturalId !== null
            ? $provider.'|'.$eventName.'|'.$naturalId
            : $provider.'|'.json_encode($payload);

        return hash('sha256', $basis);
    }

    /**
     * Verifies a Svix-format signature: HMAC-SHA256 over "{id}.{timestamp}.{body}"
     * using the webhook secret, checked against every "v1,<signature>" entry in
     * the header (Svix may include more than one during secret rotation).
     * Requests older than 5 minutes are rejected to block replaying a captured payload.
     */
    protected function verifyResendSignature(Request $request, string $secret): bool
    {
        $id = $request->header('svix-id');
        $timestamp = $request->header('svix-timestamp');
        $signatureHeader = $request->header('svix-signature');

        if (!$id || !$timestamp || !$signatureHeader) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $secretBytes = base64_decode(preg_replace('/^whsec_/', '', $secret));
        $signedContent = $id.'.'.$timestamp.'.'.$request->getContent();
        $expectedSignature = base64_encode(hash_hmac('sha256', $signedContent, $secretBytes, true));

        foreach (explode(' ', $signatureHeader) as $part) {
            $pieces = explode(',', $part, 2);
            $version = $pieces[0] ?? '';
            $signature = $pieces[1] ?? '';

            if ($version === 'v1' && $signature !== '' && hash_equals($expectedSignature, $signature)) {
                return true;
            }
        }

        return false;
    }
}
