<?php

namespace App\Services\Webhooks;

use App\Models\CampaignRecipient;
use App\Models\SuppressionList;
use App\Models\WebhookEvent;
use Illuminate\Database\QueryException;

/**
 * Applies one normalized webhook event to the database. Idempotent: the
 * WebhookEvent row's unique dedupe_key is created FIRST, before any side
 * effect - if that insert fails because the key already exists, this exact
 * event was already handled (a provider retry), so nothing further happens.
 */
class WebhookEventProcessor
{
    /**
     * @param array{type: string, message_id: ?string, email: ?string}|null $event
     * @param array<string, mixed> $rawPayload
     */
    public function process(string $provider, ?array $event, string $dedupeKey, array $rawPayload): void
    {
        $type = $event['type'] ?? 'ignored';

        $recipient = null;
        if ($event && !empty($event['message_id'])) {
            $recipient = CampaignRecipient::where('provider_message_id', $event['message_id'])->first();
        }

        try {
            WebhookEvent::create([
                'provider' => $provider,
                'event_type' => $type,
                'dedupe_key' => $dedupeKey,
                'campaign_recipient_id' => $recipient?->id,
                'payload' => json_encode($rawPayload),
            ]);
        } catch (QueryException $e) {
            if ($this->isDuplicateKeyError($e)) {
                return; // Already processed this exact event - stop here.
            }

            throw $e;
        }

        if ($recipient) {
            $this->applyToRecipient($recipient, $type);
        }
    }

    protected function applyToRecipient(CampaignRecipient $recipient, string $type): void
    {
        $campaign = $recipient->campaign;
        $contact = $recipient->contact;

        if (!$campaign || !$contact) {
            return;
        }

        switch ($type) {
            case 'delivered':
                $recipient->update(['delivered_at' => $recipient->delivered_at ?? now()]);
                break;

            case 'bounced':
                $recipient->update(['status' => 'bounced', 'bounced_at' => now()]);
                $contact->update(['status' => 'bounced']);
                SuppressionList::updateOrCreate(
                    ['organization_id' => $campaign->organization_id, 'email' => strtolower($contact->email)],
                    ['reason' => 'bounced']
                );
                break;

            case 'complained':
                $recipient->update(['status' => 'complained', 'complained_at' => now()]);
                $contact->update(['status' => 'unsubscribed']);
                SuppressionList::updateOrCreate(
                    ['organization_id' => $campaign->organization_id, 'email' => strtolower($contact->email)],
                    ['reason' => 'complained']
                );
                break;

            case 'unsubscribed':
                $contact->update(['status' => 'unsubscribed']);
                SuppressionList::updateOrCreate(
                    ['organization_id' => $campaign->organization_id, 'email' => strtolower($contact->email)],
                    ['reason' => 'unsubscribed']
                );
                break;
        }
    }

    protected function isDuplicateKeyError(QueryException $e): bool
    {
        // SQLite reports this as a message string; MySQL uses SQLSTATE 23000 / driver code 1062.
        return str_contains($e->getMessage(), 'UNIQUE constraint failed')
            || ($e->errorInfo[1] ?? null) === 1062;
    }
}
