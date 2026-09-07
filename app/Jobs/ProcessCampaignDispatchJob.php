<?php

namespace App\Jobs;

use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCampaignDispatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $campaignId;

    /**
     * Create a new job instance.
     *
     * @param int $campaignId
     */
    public function __construct(int $campaignId)
    {
        $this->campaignId = $campaignId;
    }

    /**
     * Execute the job to queue individual recipient send jobs.
     */
    public function handle(): void
    {
        $campaign = Campaign::with('recipients')->find($this->campaignId);

        if (!$campaign) {
            return;
        }

        // Update campaign status to sending
        $campaign->update(['status' => 'sending']);

        $pendingRecipients = $campaign->recipients()->where('status', 'pending')->get();

        if ($pendingRecipients->isEmpty()) {
            $campaign->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
            return;
        }

        // Dispatch individual recipient email jobs
        foreach ($pendingRecipients as $recipient) {
            SendCampaignEmailJob::dispatch($campaign->id, $recipient->id);
        }
    }
}