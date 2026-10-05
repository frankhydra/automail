<?php

namespace App\Services;

use App\Models\Automation;
use App\Models\AutomationEvent;
use App\Models\AutomationNode;
use App\Models\AutomationRun;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\SuppressionList;
use App\Models\Template;
use Illuminate\Database\QueryException;
use Throwable;

/**
 * Runs journeys. A "run" is one contact travelling through one automation.
 *
 * The engine does the steps one after another until it reaches a Wait (then it
 * parks the run until the wait is over) or the end of the journey.
 *
 *  - email      sends through a hidden system campaign so tracking, the unsubscribe
 *               link, analytics and plan limits behave like a normal campaign
 *  - wait       parks the run for hours or days
 *  - condition  looks at the contact's most recent email in this run (opened /
 *               clicked) and follows the Yes or No branch
 *
 * Safety: a run is claimed atomically before it is worked on, so two workers can
 * never process it at once; an email already sent is never sent again; and
 * unsubscribed or suppressed contacts are checked before every single step.
 */
class AutomationEngine
{
    protected const MAX_ATTEMPTS = 3;
    protected const MAX_STEPS_PER_PASS = 50;

    public function __construct(
        protected EmailDeliveryService $delivery,
        protected PlanLimitService $planLimits,
    ) {
    }

    /**
     * Put a contact into a journey. Returns the new run, or null when nothing started
     * (automation not active, no steps, or the contact already went through it).
     */
    public function enroll(Automation $automation, Contact $contact): ?AutomationRun
    {
        if ($automation->status !== 'active') {
            return null;
        }

        $first = $automation->nodes()->whereNull('parent_id')->whereNull('branch')->orderBy('id')->first();

        if (!$first) {
            return null;
        }

        try {
            $run = AutomationRun::firstOrCreate(
                ['automation_id' => $automation->id, 'contact_id' => $contact->id],
                ['status' => 'active', 'current_node_id' => $first->id, 'next_run_at' => now()]
            );
        } catch (QueryException) {
            return null; // lost a race with another process; the contact is already in
        }

        if (!$run->wasRecentlyCreated) {
            return null;
        }

        $this->log($run, null, 'entered', 'Contact entered the journey.');

        return $run;
    }

    /**
     * Work on one run if it is due. Safe to call repeatedly or from several workers.
     */
    public function advance(int $runId): void
    {
        $claimed = AutomationRun::due()->where('id', $runId)->update(['claimed_at' => now()]);

        if (!$claimed) {
            return; // not due, already finished, or another worker has it
        }

        $run = AutomationRun::with(['automation', 'contact'])->find($runId);

        if (!$run) {
            return;
        }

        try {
            $this->process($run);
        } catch (Throwable $e) {
            report($e);
            $this->handleError($run, $e);
        }
    }

    protected function process(AutomationRun $run): void
    {
        $automation = $run->automation;
        $contact = $run->contact;

        // Paused (or removed) after the run was picked: hand it back untouched.
        if (!$automation || $automation->status !== 'active' || !$contact) {
            $run->update(['claimed_at' => null]);

            return;
        }

        for ($pass = 0; $pass < self::MAX_STEPS_PER_PASS; $pass++) {
            if (!$this->canEmail($contact)) {
                $this->finish($run, 'cancelled', 'cancelled', 'The contact is unsubscribed or suppressed.');

                return;
            }

            $node = $run->current_node_id ? AutomationNode::find($run->current_node_id) : null;

            if (!$node) {
                $this->finish($run, 'completed', 'completed', 'Reached the end of the journey.');

                return;
            }

            switch ($node->type) {
                case 'email':
                    $result = $this->sendEmail($automation, $run, $node, $contact);

                    if ($result === 'limit') {
                        $this->log($run, $node, 'delayed', 'Monthly email limit reached. Will try again in an hour.');
                        $run->update(['next_run_at' => now()->addHour(), 'claimed_at' => null]);

                        return;
                    }

                    if ($result !== 'sent') {
                        $this->finish($run, 'failed', 'failed', $result);

                        return;
                    }

                    $run->current_node_id = $this->childOf($node, null);
                    $run->save();
                    break;

                case 'wait':
                    $amount = max(1, (int) ($node->config['amount'] ?? 1));
                    $until = ($node->config['unit'] ?? 'days') === 'hours' ? now()->addHours($amount) : now()->addDays($amount);

                    $this->log($run, $node, 'waiting', "Waiting {$amount} ".(($node->config['unit'] ?? 'days') === 'hours' ? 'hour(s)' : 'day(s)').'.');

                    $run->update([
                        'current_node_id' => $this->childOf($node, null),
                        'next_run_at' => $until,
                        'claimed_at' => null,
                    ]);

                    return;

                case 'condition':
                    $yes = $this->conditionIsMet($run, $node);
                    $this->log($run, $node, $yes ? 'condition_yes' : 'condition_no', $yes ? 'Condition met: following Yes.' : 'Condition not met: following No.');

                    $run->current_node_id = $this->childOf($node, $yes ? 'yes' : 'no');
                    $run->save();
                    break;

                default:
                    $this->finish($run, 'failed', 'failed', "Unknown step type \"{$node->type}\".");

                    return;
            }
        }

        // Defensive: a journey can never legitimately need this many steps in one pass.
        $this->finish($run, 'failed', 'failed', 'Too many steps in one pass.');
    }

    /**
     * @return string 'sent', 'limit', or an error message
     */
    protected function sendEmail(Automation $automation, AutomationRun $run, AutomationNode $node, Contact $contact): string
    {
        $template = Template::where('organization_id', $automation->organization_id)
            ->find($node->config['template_id'] ?? 0);

        if (!$template) {
            return 'The email template for this step no longer exists.';
        }

        $identity = $automation->sendingIdentity;

        if (!$identity || $identity->verification_status !== 'verified') {
            return 'The automation has no verified sending identity.';
        }

        if (!$this->planLimits->canSendEmails($automation->organization, 1)) {
            return 'limit';
        }

        $campaign = Campaign::updateOrCreate(
            ['automation_id' => $automation->id, 'automation_node_id' => $node->id],
            [
                'organization_id' => $automation->organization_id,
                'sending_identity_id' => $identity->id,
                'template_id' => $template->id,
                'name' => '[Automation] '.$automation->name,
                // The template is read fresh each time, so editing it updates live journeys.
                'subject' => $template->subject ?: $automation->name,
                'body' => $template->body,
                'status' => 'automation',
            ]
        );

        $recipient = CampaignRecipient::firstOrCreate(
            ['campaign_id' => $campaign->id, 'contact_id' => $contact->id],
            ['status' => 'pending']
        );

        if ($recipient->status === 'pending') {
            if (!$this->delivery->sendRecipientEmail($campaign, $recipient)) {
                return (string) ($recipient->fresh()->error_message ?: 'The email provider rejected the email.');
            }
        } elseif ($recipient->status !== 'sent') {
            return 'This contact could not be emailed for this step ('.$recipient->status.').';
        }

        $run->last_recipient_id = $recipient->id;
        $run->save();

        $this->log($run, $node, 'email_sent', 'Sent "'.$template->name.'".');

        return 'sent';
    }

    protected function conditionIsMet(AutomationRun $run, AutomationNode $node): bool
    {
        $recipient = $run->last_recipient_id ? CampaignRecipient::find($run->last_recipient_id) : null;

        if (!$recipient) {
            return false;
        }

        // A click implies the email was opened, even if the open pixel was blocked.
        if (($node->config['check'] ?? 'opened') === 'clicked') {
            return $recipient->clicked_at !== null;
        }

        return $recipient->opened_at !== null || $recipient->clicked_at !== null;
    }

    protected function childOf(AutomationNode $node, ?string $branch): ?int
    {
        $query = AutomationNode::where('parent_id', $node->id);

        $branch === null ? $query->whereNull('branch') : $query->where('branch', $branch);

        return $query->orderBy('id')->value('id');
    }

    protected function canEmail(Contact $contact): bool
    {
        if ($contact->status !== 'subscribed') {
            return false;
        }

        return !SuppressionList::where('organization_id', $contact->organization_id)
            ->where('email', strtolower($contact->email))
            ->exists();
    }

    protected function finish(AutomationRun $run, string $status, string $event, string $detail): void
    {
        $this->log($run, null, $event, $detail);

        $run->update([
            'status' => $status,
            'next_run_at' => null,
            'claimed_at' => null,
            'completed_at' => now(),
        ]);
    }

    /**
     * Unexpected error (database hiccup, ...): retry in 5 minutes, and give up after 3 tries.
     */
    protected function handleError(AutomationRun $run, Throwable $e): void
    {
        $run = AutomationRun::find($run->id);

        if (!$run) {
            return;
        }

        $attempts = $run->attempts + 1;
        $message = mb_substr($e->getMessage(), 0, 300);

        if ($attempts >= self::MAX_ATTEMPTS) {
            $run->attempts = $attempts;
            $run->save();
            $this->finish($run, 'failed', 'failed', "Gave up after {$attempts} tries: {$message}");

            return;
        }

        $this->log($run, null, 'error', "Will retry in 5 minutes: {$message}");
        $run->update(['attempts' => $attempts, 'next_run_at' => now()->addMinutes(5), 'claimed_at' => null]);
    }

    protected function log(AutomationRun $run, ?AutomationNode $node, string $event, ?string $detail = null): void
    {
        AutomationEvent::create([
            'automation_run_id' => $run->id,
            'automation_node_id' => $node?->id,
            'event' => $event,
            'detail' => $detail !== null ? mb_substr($detail, 0, 500) : null,
            'created_at' => now(),
        ]);
    }
}
