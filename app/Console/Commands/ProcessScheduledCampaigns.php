<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Services\CampaignDispatchService;
use Illuminate\Console\Command;

/**
 * Runs every minute (see bootstrap/app.php). Finds every campaign whose
 * scheduled send time has arrived and hands each one to
 * CampaignDispatchService - the exact same logic "Send Now" uses.
 */
class ProcessScheduledCampaigns extends Command
{
    protected $signature = 'campaigns:process-scheduled';

    protected $description = 'Dispatch scheduled campaigns whose scheduled time has arrived.';

    public function handle(CampaignDispatchService $dispatcher): int
    {
        $due = Campaign::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($due->isEmpty()) {
            $this->line('No scheduled campaigns are due.');

            return self::SUCCESS;
        }

        foreach ($due as $campaign) {
            $result = $dispatcher->execute($campaign, (string) ($campaign->tag_filter ?? ''));

            if ($result['ok']) {
                $this->info("Dispatched scheduled campaign #{$campaign->id} ({$campaign->name}).");
            } else {
                $this->warn("Skipped campaign #{$campaign->id}: {$result['error']}");
            }
        }

        return self::SUCCESS;
    }
}
