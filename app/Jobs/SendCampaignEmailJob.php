<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\SuppressionList;
use App\Services\EmailDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

class SendCampaignEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Attempts before the job is considered failed. Applies to unexpected exceptions
     * (database hiccup, network error, ...). A provider that answers "rejected" is
     * recorded as a failed recipient straight away by EmailDeliveryService.
     */
    public int $tries = 3;

    public int $campaignId;
    public int $recipientId;

    public function __construct(int $campaignId, int $recipientId)
    {
        $this->campaignId = $campaignId;
        $this->recipientId = $recipientId;
    }

    /**
     * Seconds to wait before retry 2 and retry 3.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(EmailDeliveryService $deliveryService): void
    {
        $campaign = Campaign::find($this->campaignId);
        $recipient = CampaignRecipient::with('contact')->find($this->recipientId);

        if (!$campaign || !$recipient || $recipient->campaign_id !== $campaign->id) {
            return;
        }

        // Only deliver while the campaign is actively sending (not cancelled/finished).
        if ($campaign->status !== 'sending') {
            return;
        }

        // Idempotency: a retried or duplicated job must never email someone twice.
        if ($recipient->status !== 'pending') {
            $campaign->markFinishedIfComplete();
            return;
        }

        $contact = $recipient->contact;

        // Enforce unsubscribes/bounces at the moment of sending, not only when the
        // audience was snapshotted.
        $suppressed = $contact !== null && (
            $contact->status !== 'subscribed'
            || SuppressionList::where('organization_id', $campaign->organization_id)
                ->where('email', strtolower($contact->email))
                ->exists()
        );

        if ($suppressed) {
            $recipient->update([
                'status' => 'suppressed',
                'error_message' => 'Contact is unsubscribed or on the suppression list.',
            ]);
        } else {
            $deliveryService->sendRecipientEmail($campaign, $recipient);
        }

        $campaign->markFinishedIfComplete();
    }

    /**
     * Called by Laravel after the last attempt has thrown. Without this the recipient
     * would stay "pending" forever and the campaign would never finish.
     */
    public function failed(Throwable $exception): void
    {
        $recipient = CampaignRecipient::find($this->recipientId);

        if ($recipient && $recipient->status === 'pending') {
            $recipient->update([
                'status' => 'failed',
                'error_message' => Str::limit($exception->getMessage(), 500),
            ]);
        }

        Campaign::find($this->campaignId)?->markFinishedIfComplete();
    }
}
