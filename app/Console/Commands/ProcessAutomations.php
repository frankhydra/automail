<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAutomationRunJob;
use App\Models\AutomationRun;
use Illuminate\Console\Command;

/**
 * Runs every minute (see bootstrap/app.php). Finds journeys whose next step is due
 * and queues one job per contact. Only runs of ACTIVE automations are picked up,
 * so pausing an automation freezes everyone where they are.
 */
class ProcessAutomations extends Command
{
    protected $signature = 'automations:process';

    protected $description = 'Queue the next step for every automation run that is due.';

    public function handle(): int
    {
        $ids = AutomationRun::due()
            ->whereHas('automation', fn ($query) => $query->where('status', 'active'))
            ->orderBy('next_run_at')
            ->limit(500)
            ->pluck('id');

        foreach ($ids as $id) {
            ProcessAutomationRunJob::dispatch($id);
        }

        $this->line($ids->isEmpty() ? 'No automation steps are due.' : "Queued {$ids->count()} automation step(s).");

        return self::SUCCESS;
    }
}
