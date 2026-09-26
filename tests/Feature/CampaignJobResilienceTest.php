<?php

namespace Tests\Feature;

use App\Jobs\SendCampaignEmailJob;
use App\Models\Campaign;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class CampaignJobResilienceTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_a_retried_job_never_sends_twice(): void
    {
        [, $org] = $this->makeTenant();
        $provider = $this->useFakeProvider();

        $identity = $this->makeIdentity($org);
        $contact = $this->makeContact($org);
        $campaign = $this->makeCampaign($org, $identity, [$contact], ['status' => 'sending']);
        $recipient = $campaign->recipients()->first();

        (new SendCampaignEmailJob($campaign->id, $recipient->id))->handle(app(\App\Services\EmailDeliveryService::class));
        // Simulate a duplicate/retried run of the same job.
        (new SendCampaignEmailJob($campaign->id, $recipient->id))->handle(app(\App\Services\EmailDeliveryService::class));

        $this->assertCount(1, $provider->sent);
        $this->assertSame('sent', $campaign->fresh()->status);
    }

    public function test_a_failed_job_marks_the_recipient_failed_and_the_campaign_finishes(): void
    {
        [, $org] = $this->makeTenant();
        $this->useFakeProvider(succeed: false);

        $identity = $this->makeIdentity($org);
        $contact = $this->makeContact($org);
        $campaign = $this->makeCampaign($org, $identity, [$contact], ['status' => 'sending']);
        $recipient = $campaign->recipients()->first();

        $job = new SendCampaignEmailJob($campaign->id, $recipient->id);
        $job->failed(new Exception('boom'));

        $this->assertSame('failed', $recipient->fresh()->status);
        $this->assertSame('failed', $campaign->fresh()->status);
    }

    public function test_campaign_finishes_as_sent_when_at_least_one_recipient_succeeds(): void
    {
        [, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org);
        $ok = $this->makeContact($org);
        $bad = $this->makeContact($org);
        $campaign = $this->makeCampaign($org, $identity, [$ok, $bad], ['status' => 'sending']);

        $campaign->recipients()->where('contact_id', $ok->id)->update(['status' => 'sent']);
        $campaign->recipients()->where('contact_id', $bad->id)->update(['status' => 'failed']);

        $campaign->markFinishedIfComplete();

        $this->assertSame('sent', $campaign->fresh()->status);
    }
}
