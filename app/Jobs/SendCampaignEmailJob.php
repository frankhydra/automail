<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Services\EmailDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCampaignEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $campaignId;
    public int $recipientId;

    /**
     * Create a new job instance.
     *
     * @param int $campaignId
     * @param int $recipientId
     */
    public function __construct(int $campaignId, int $recipientId)
    {
        $this->campaignId = $campaignId;
        $this->recipientId = $recipientId;
    }

    /**
     * Execute the email sending job for a specific recipient.
     */
    public function handle(EmailDeliveryService $deliveryService): void
    {
        $campaign = Campaign::find($this->campaignId);
        $recipient = CampaignRecipient::with('contact')->find($this->recipientId);

        if (!$campaign || !$recipient) {
            return;
        }

        // Deliver personalized email
        $deliveryService->sendRecipientEmail($campaign, $recipient);

        // Check if all recipients for this campaign have finished processing
        $remainingPending = $campaign->recipients()->where('status', 'pending')->count();
        if ($remainingPending === 0) {
            $campaign->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }
    }
}