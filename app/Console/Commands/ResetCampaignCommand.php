<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use Illuminate\Console\Command;

class ResetCampaignCommand extends Command
{
    protected $signature = 'campaign:reset {id : The ID of the campaign to reset}';
    protected $description = 'Reset a stuck or queued campaign back to draft status for re-testing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $campaignId = $this->argument('id');
        $campaign = Campaign::with('recipients')->find($campaignId);

        if (!$campaign) {
            $this->error("Campaign with ID {$campaignId} was not found.");
            return self::FAILURE;
        }

        // Reset campaign status back to draft and clear sent timestamp
        $campaign->update([
            'status' => 'draft',
            'sent_at' => null,
        ]);

        // Reset all recipient statuses back to pending
        $campaign->recipients()->update([
            'status' => 'pending',
            'error_message' => null,
            'sent_at' => null,
        ]);

        $this->info("Successfully reset campaign #{$campaignId} ('{$campaign->name}') back to draft status and cleared all recipient snapshots.");
        
        return self::SUCCESS;
    }
}