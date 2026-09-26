<?php

namespace App\Services;

use App\Jobs\ProcessCampaignDispatchJob;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Support\Facades\DB;

/**
 * The single place that turns a campaign from "not sending yet" into "queued
 * for delivery". Used both by an immediate "Send Now" dispatch and by the
 * scheduler firing a campaign whose scheduled time has arrived - so the two
 * paths can never drift apart or duplicate a send.
 */
class CampaignDispatchService
{
    public function __construct(private readonly PlanLimitService $planLimits)
    {
    }

    /**
     * Narrow the snapshot audience by tag (if given), skip anyone no longer
     * subscribed, check the plan's monthly email limit, atomically claim the
     * campaign, and hand it to the queue.
     *
     * @return array{ok: bool, error: ?string}
     */
    public function execute(Campaign $campaign, string $tagFilter): array
    {
        $tagFilter = trim($tagFilter);
        $sendIds = [];
        $skipIds = [];

        $campaign->recipients()
            ->where('status', 'pending')
            ->with('contact')
            ->chunkById(500, function ($recipients) use (&$sendIds, &$skipIds, $tagFilter) {
                foreach ($recipients as $recipient) {
                    $contact = $recipient->contact;

                    $eligible = $contact
                        && $contact->status === 'subscribed'
                        && ($tagFilter === '' || $contact->hasAnyTag($tagFilter));

                    if ($eligible) {
                        $sendIds[] = $recipient->id;
                    } else {
                        $skipIds[] = $recipient->id;
                    }
                }
            });

        if (empty($sendIds)) {
            return ['ok' => false, 'error' => 'No subscribed contacts in this campaign match the selected tag filter.'];
        }

        $organization = $campaign->organization;

        if ($organization && !$this->planLimits->canSendEmails($organization, count($sendIds))) {
            $limit = $this->planLimits->limits($organization)['monthly_emails'];
            $used = $this->planLimits->monthlyEmailsUsed($organization);

            return ['ok' => false, 'error' => "Sending this campaign (".count($sendIds)." emails) would exceed your plan's monthly limit of {$limit} (already sent {$used} this month). Upgrade your plan or wait until next month."];
        }

        // Claim from either "draft" (immediate dispatch) or "scheduled" (the
        // scheduler firing it) - whichever the campaign is currently in.
        // Atomic, so a campaign is never dispatched twice.
        $claimed = DB::transaction(function () use ($campaign, $skipIds) {
            $updated = Campaign::where('id', $campaign->id)
                ->whereIn('status', ['draft', 'scheduled'])
                ->update(['status' => 'queued']);

            if ($updated === 0) {
                return false;
            }

            foreach (array_chunk($skipIds, 500) as $chunk) {
                CampaignRecipient::whereIn('id', $chunk)->update([
                    'status' => 'skipped',
                    'error_message' => 'Outside the selected tag filter, or no longer subscribed.',
                ]);
            }

            return true;
        });

        if (!$claimed) {
            return ['ok' => false, 'error' => 'Campaign has already been dispatched, scheduled, or cancelled.'];
        }

        ProcessCampaignDispatchJob::dispatch($campaign->id);

        return ['ok' => true, 'error' => null];
    }
}
