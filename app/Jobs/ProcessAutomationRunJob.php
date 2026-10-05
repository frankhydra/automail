<?php

namespace App\Jobs;

use App\Services\AutomationEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Works one automation run. The engine claims the run atomically, so it does not
 * matter if this job is queued twice or retried.
 */
class ProcessAutomationRunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // the engine does its own retrying

    public function __construct(public int $runId)
    {
    }

    public function handle(AutomationEngine $engine): void
    {
        $engine->advance($this->runId);
    }
}
