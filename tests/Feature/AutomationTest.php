<?php

namespace Tests\Feature;

use App\Jobs\ProcessAutomationRunJob;
use App\Models\Automation;
use App\Models\AutomationRun;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\SuppressionList;
use App\Services\AutomationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class AutomationTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    /** email -> wait 1 day -> condition(opened): yes -> email B, no -> email C */
    protected function journey(Organization $org, string $status = 'active', string $trigger = 'contact_added', ?array $config = null): Automation
    {
        $a = $org->templates()->create(['name' => 'Welcome', 'subject' => 'Welcome {{first_name}}', 'body' => '<p>Hi {{first_name}}</p>']);
        $b = $org->templates()->create(['name' => 'Promo', 'subject' => 'VIP promo', 'body' => '<p>Promo</p>']);
        $c = $org->templates()->create(['name' => 'Reminder', 'subject' => 'Reminder', 'body' => '<p>Reminder</p>']);

        $automation = $org->automations()->create([
            'name' => 'Welcome series',
            'status' => $status,
            'trigger_type' => $trigger,
            'trigger_config' => $config,
            'sending_identity_id' => $this->makeIdentity($org)->id,
        ]);

        $email1 = $automation->nodes()->create(['type' => 'email', 'config' => ['template_id' => $a->id]]);
        $wait = $automation->nodes()->create(['type' => 'wait', 'parent_id' => $email1->id, 'config' => ['amount' => 1, 'unit' => 'days']]);
        $cond = $automation->nodes()->create(['type' => 'condition', 'parent_id' => $wait->id, 'config' => ['check' => 'opened']]);
        $automation->nodes()->create(['type' => 'email', 'parent_id' => $cond->id, 'branch' => 'yes', 'config' => ['template_id' => $b->id]]);
        $automation->nodes()->create(['type' => 'email', 'parent_id' => $cond->id, 'branch' => 'no', 'config' => ['template_id' => $c->id]]);

        return $automation;
    }

    protected function engine(): AutomationEngine
    {
        return app(AutomationEngine::class);
    }

    public function test_a_new_contact_enters_an_active_journey_but_not_a_draft(): void
    {
        [, $org] = $this->makeTenant();
        $live = $this->journey($org, 'active');
        $draft = $this->journey($org, 'draft');

        $contact = $this->makeContact($org);

        $this->assertSame(1, $live->runs()->where('contact_id', $contact->id)->count());
        $this->assertSame(0, $draft->runs()->count());
    }

    public function test_unsubscribed_contacts_and_other_organizations_do_not_enter(): void
    {
        [, $org] = $this->makeTenant('Acme');
        [, $other] = $this->makeTenant('Other');
        $live = $this->journey($org);

        $this->makeContact($org, ['status' => 'unsubscribed']);
        $this->makeContact($other);

        $this->assertSame(0, $live->runs()->count());
    }

    public function test_the_tag_trigger_only_fires_for_the_chosen_tag(): void
    {
        [, $org] = $this->makeTenant();
        $live = $this->journey($org, 'active', 'tag_added', ['tag' => 'vip']);

        $plain = $this->makeContact($org, ['tags' => 'newsletter']);
        $this->assertSame(0, $live->runs()->count());

        $plain->update(['tags' => 'newsletter, VIP']);
        $this->assertSame(1, $live->runs()->where('contact_id', $plain->id)->count());

        $born = $this->makeContact($org, ['tags' => 'vip']);
        $this->assertSame(1, $live->runs()->where('contact_id', $born->id)->count());

        // Saving again must not start a second run for the same contact.
        $plain->update(['tags' => 'newsletter, vip, extra']);
        $this->assertSame(2, $live->runs()->count());
    }

    public function test_the_first_email_is_sent_then_the_run_waits(): void
    {
        [, $org] = $this->makeTenant();
        $fake = $this->useFakeProvider();
        $this->journey($org);
        $contact = $this->makeContact($org, ['first_name' => 'Amy']);
        $run = AutomationRun::first();

        $this->engine()->advance($run->id);

        $this->assertCount(1, $fake->sent);
        $this->assertSame($contact->email, $fake->sent[0]['toEmail']);
        $this->assertSame('Welcome Amy', $fake->sent[0]['subject']);

        $run->refresh();
        $this->assertSame('active', $run->status);
        $this->assertTrue($run->next_run_at->isFuture());
        $this->assertNotNull($run->last_recipient_id);
    }

    public function test_a_run_that_is_not_due_yet_is_left_alone(): void
    {
        [, $org] = $this->makeTenant();
        $fake = $this->useFakeProvider();
        $this->journey($org);
        $this->makeContact($org);
        $run = AutomationRun::first();

        $this->engine()->advance($run->id); // email 1, now waiting a day
        $this->engine()->advance($run->id); // too early

        $this->assertCount(1, $fake->sent);
    }

    public function test_the_no_branch_is_followed_when_the_email_was_not_opened(): void
    {
        [, $org] = $this->makeTenant();
        $fake = $this->useFakeProvider();
        $this->journey($org);
        $this->makeContact($org);
        $run = AutomationRun::first();

        $this->engine()->advance($run->id);
        $this->travel(2)->days();
        $this->engine()->advance($run->id);

        $this->assertCount(2, $fake->sent);
        $this->assertSame('Reminder', $fake->sent[1]['subject']);
        $this->assertSame('completed', $run->fresh()->status);
    }

    public function test_the_yes_branch_is_followed_when_the_email_was_opened(): void
    {
        [, $org] = $this->makeTenant();
        $fake = $this->useFakeProvider();
        $this->journey($org);
        $this->makeContact($org);
        $run = AutomationRun::first();

        $this->engine()->advance($run->id);
        CampaignRecipient::find($run->fresh()->last_recipient_id)->update(['opened_at' => now()]);

        $this->travel(2)->days();
        $this->engine()->advance($run->id);

        $this->assertSame('VIP promo', $fake->sent[1]['subject']);
        $this->assertSame('completed', $run->fresh()->status);
    }

    public function test_an_unsubscribe_during_the_wait_cancels_the_run(): void
    {
        [, $org] = $this->makeTenant();
        $fake = $this->useFakeProvider();
        $this->journey($org);
        $contact = $this->makeContact($org);
        $run = AutomationRun::first();

        $this->engine()->advance($run->id);
        $contact->update(['status' => 'unsubscribed']);

        $this->travel(2)->days();
        $this->engine()->advance($run->id);

        $this->assertCount(1, $fake->sent);
        $this->assertSame('cancelled', $run->fresh()->status);
    }

    public function test_a_suppressed_address_is_never_emailed(): void
    {
        [, $org] = $this->makeTenant();
        $fake = $this->useFakeProvider();
        $this->journey($org);
        $contact = $this->makeContact($org);
        SuppressionList::create(['organization_id' => $org->id, 'email' => strtolower($contact->email), 'reason' => 'bounced']);

        $this->engine()->advance(AutomationRun::first()->id);

        $this->assertCount(0, $fake->sent);
        $this->assertSame('cancelled', AutomationRun::first()->status);
    }

    public function test_working_the_same_run_twice_never_double_sends(): void
    {
        [, $org] = $this->makeTenant();
        $fake = $this->useFakeProvider();
        $this->journey($org);
        $this->makeContact($org);
        $run = AutomationRun::first();

        $this->engine()->advance($run->id);
        $this->engine()->advance($run->id);

        $this->assertCount(1, $fake->sent);
    }

    public function test_a_paused_journey_is_not_processed_and_resumes_later(): void
    {
        [, $org] = $this->makeTenant();
        $fake = $this->useFakeProvider();
        $automation = $this->journey($org);
        $this->makeContact($org);
        $run = AutomationRun::first();

        $automation->update(['status' => 'paused']);
        $this->engine()->advance($run->id);
        $this->assertCount(0, $fake->sent);
        $this->assertNull($run->fresh()->claimed_at);

        $automation->update(['status' => 'active']);
        $this->engine()->advance($run->id);
        $this->assertCount(1, $fake->sent);
    }

    public function test_a_provider_failure_fails_the_run_without_retrying_forever(): void
    {
        [, $org] = $this->makeTenant();
        $this->useFakeProvider(false);
        $this->journey($org);
        $this->makeContact($org);
        $run = AutomationRun::first();

        $this->engine()->advance($run->id);

        $this->assertSame('failed', $run->fresh()->status);
    }

    public function test_the_scheduler_command_queues_only_due_runs_of_active_journeys(): void
    {
        [, $org] = $this->makeTenant();
        $live = $this->journey($org);
        $this->makeContact($org);
        $dueRun = AutomationRun::first();

        $future = $this->makeContact($org);
        AutomationRun::where('contact_id', $future->id)->update(['next_run_at' => now()->addDay()]);

        Bus::fake();
        $this->artisan('automations:process')->assertSuccessful();

        Bus::assertDispatched(ProcessAutomationRunJob::class, fn ($job) => $job->runId === $dueRun->id);
        Bus::assertDispatchedTimes(ProcessAutomationRunJob::class, 1);

        Bus::fake();
        $live->update(['status' => 'paused']);
        $this->artisan('automations:process')->assertSuccessful();
        Bus::assertNothingDispatched();
    }

    public function test_automation_emails_do_not_appear_in_the_campaign_list(): void
    {
        [$user, $org] = $this->makeTenant();
        $this->useFakeProvider();
        $this->journey($org);
        $this->makeContact($org);
        $this->engine()->advance(AutomationRun::first()->id);

        $this->assertSame(1, \App\Models\Campaign::where('status', 'automation')->count());

        $this->actingAs($user)->get(route('campaigns.index'))->assertOk()->assertDontSee('[Automation]');
    }

    public function test_automation_emails_count_toward_the_monthly_plan_usage(): void
    {
        [, $org] = $this->makeTenant();
        $this->useFakeProvider();
        $this->journey($org);
        $this->makeContact($org);
        $this->engine()->advance(AutomationRun::first()->id);

        $this->assertSame(1, app(\App\Services\PlanLimitService::class)->monthlyEmailsUsed($org));
    }

    // ---------------------------------------------------------------- screens

    protected function payload(Organization $org, array $overrides = []): array
    {
        $template = $org->templates()->create(['name' => 'T', 'subject' => 'S', 'body' => '<p>x</p>']);

        $steps = [
            ['type' => 'email', 'template_id' => $template->id],
            ['type' => 'wait', 'amount' => 2, 'unit' => 'days'],
            ['type' => 'condition', 'check' => 'opened', 'yes' => [['type' => 'email', 'template_id' => $template->id]], 'no' => []],
        ];

        return array_merge([
            'name' => 'Welcome',
            'trigger_type' => 'contact_added',
            'sending_identity_id' => $this->makeIdentity($org)->id,
            'steps_json' => json_encode($steps),
        ], $overrides);
    }

    public function test_the_pages_load(): void
    {
        [$user, $org] = $this->makeTenant();
        $automation = $this->journey($org);

        $this->actingAs($user)->get(route('automations.index'))->assertOk()->assertSee('Welcome series');
        $this->actingAs($user)->get(route('automations.create'))->assertOk();
        $this->actingAs($user)->get(route('automations.edit', $automation->id))->assertOk();
    }

    public function test_saving_builds_the_step_tree(): void
    {
        [$user, $org] = $this->makeTenant();

        $this->actingAs($user)->post(route('automations.store'), $this->payload($org))->assertSessionHasNoErrors();

        $automation = $org->automations()->first();
        $this->assertSame('draft', $automation->status);

        $tree = $automation->stepTree();
        $this->assertSame(['email', 'wait', 'condition'], array_column($tree, 'type'));
        $this->assertCount(1, $tree[2]['yes']);
        $this->assertCount(0, $tree[2]['no']);
    }

    public function test_a_template_from_another_organization_is_refused(): void
    {
        [$user, $org] = $this->makeTenant('Acme');
        [, $other] = $this->makeTenant('Other');
        $foreign = $other->templates()->create(['name' => 'Theirs', 'body' => '<p>x</p>']);

        $payload = $this->payload($org, ['steps_json' => json_encode([['type' => 'email', 'template_id' => $foreign->id]])]);

        $this->actingAs($user)->post(route('automations.store'), $payload)->assertSessionHasErrors('steps_json');
        $this->assertSame(0, $org->automations()->count());
    }

    public function test_a_condition_must_be_last_and_follow_an_email(): void
    {
        [$user, $org] = $this->makeTenant();
        $template = $org->templates()->create(['name' => 'T', 'body' => '<p>x</p>']);

        $notLast = [['type' => 'email', 'template_id' => $template->id], ['type' => 'condition', 'check' => 'opened', 'yes' => [], 'no' => []], ['type' => 'wait', 'amount' => 1, 'unit' => 'days']];
        $noEmail = [['type' => 'condition', 'check' => 'opened', 'yes' => [], 'no' => []]];
        $nested = [['type' => 'email', 'template_id' => $template->id], ['type' => 'condition', 'check' => 'opened', 'yes' => [['type' => 'condition', 'check' => 'opened', 'yes' => [], 'no' => []]], 'no' => []]];

        foreach ([$notLast, $noEmail, $nested] as $steps) {
            $this->actingAs($user)->post(route('automations.store'), $this->payload($org, ['steps_json' => json_encode($steps)]))
                ->assertSessionHasErrors('steps_json');
        }
    }

    public function test_bad_wait_values_are_refused(): void
    {
        [$user, $org] = $this->makeTenant();

        foreach ([['amount' => 0, 'unit' => 'days'], ['amount' => 999, 'unit' => 'days'], ['amount' => 2, 'unit' => 'years']] as $wait) {
            $this->actingAs($user)->post(route('automations.store'), $this->payload($org, ['steps_json' => json_encode([['type' => 'wait'] + $wait])]))
                ->assertSessionHasErrors('steps_json');
        }
    }

    public function test_steps_are_locked_once_contacts_are_in_the_journey(): void
    {
        [$user, $org] = $this->makeTenant();
        $this->useFakeProvider();
        $automation = $this->journey($org);
        $this->makeContact($org);
        $before = $automation->nodes()->count();

        $this->actingAs($user)->put(route('automations.update', $automation->id), $this->payload($org, ['name' => 'Renamed', 'steps_json' => '[]']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed', $automation->fresh()->name);
        $this->assertSame($before, $automation->nodes()->count());
    }

    public function test_switching_on_needs_a_verified_sender_and_an_email_step(): void
    {
        [$user, $org] = $this->makeTenant();

        $empty = $org->automations()->create(['name' => 'Empty', 'trigger_type' => 'contact_added', 'sending_identity_id' => $this->makeIdentity($org)->id]);
        $this->actingAs($user)->post(route('automations.activate', $empty->id))->assertSessionHasErrors('activate');

        $noSender = $this->journey($org, 'draft');
        $noSender->update(['sending_identity_id' => $this->makeIdentity($org, false)->id]);
        $this->actingAs($user)->post(route('automations.activate', $noSender->id))->assertSessionHasErrors('activate');

        $ready = $this->journey($org, 'draft');
        $this->actingAs($user)->post(route('automations.activate', $ready->id))->assertRedirect(route('automations.index'));
        $this->assertSame('active', $ready->fresh()->status);

        $this->actingAs($user)->post(route('automations.pause', $ready->id));
        $this->assertSame('paused', $ready->fresh()->status);
    }

    public function test_roles_and_tenant_isolation(): void
    {
        [$owner, $org] = $this->makeTenant('Acme');
        [$otherUser, $other] = $this->makeTenant('Other');
        $automation = $this->journey($org, 'draft');
        $viewer = $this->attachMember($org, 'viewer');
        $editor = $this->attachMember($org, 'editor');

        $this->actingAs($viewer)->post(route('automations.store'), $this->payload($org))->assertForbidden();
        $this->actingAs($editor)->post(route('automations.activate', $automation->id))->assertForbidden();
        $this->actingAs($editor)->delete(route('automations.destroy', $automation->id))->assertForbidden();

        $this->actingAs($otherUser)->get(route('automations.edit', $automation->id))->assertNotFound();
        $this->actingAs($otherUser)->post(route('automations.activate', $automation->id))->assertNotFound();
        $this->actingAs($otherUser)->delete(route('automations.destroy', $automation->id))->assertNotFound();
        $this->assertSame('draft', $automation->fresh()->status);

        $this->actingAs($otherUser)->get(route('automations.index'))->assertOk()->assertDontSee('Welcome series');
    }
}
