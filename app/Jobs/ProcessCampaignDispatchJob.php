<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCampaignDispatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $campaignId;

    public function __construct(int $campaignId)
    {
        $this->campaignId = $campaignId;
    }

    /**
     * Fan the campaign out into one SendCampaignEmailJob per pending recipient.
     */
    public function handle(): void
    {
        // Atomically claim the campaign (queued -> sending). If another run already
        // claimed it, or it is not queued, do nothing: this makes the job idempotent
        // and prevents duplicate fan-out.
        $claimed = Campaign::where('id', $this->campaignId)
            ->where('status', 'queued')
            ->update(['status' => 'sending']);

        if ($claimed === 0) {
            return;
        }

        $campaign = Campaign::find($this->campaignId);

        if (!$campaign) {
            return;
        }

        $campaign->recipients()
            ->where('status', 'pending')
            ->chunkById(500, function ($recipients) use ($campaign) {
                /** @var CampaignRecipient $recipient */
                foreach ($recipients as $recipient) {
                    SendCampaignEmailJob::dispatch($campaign->id, $recipient->id);
                }
            });

        // Covers an audience with nothing left to send.
        $campaign->markFinishedIfComplete();
    }
}
