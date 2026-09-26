<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Segment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class SegmentTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_a_segment_can_be_created_with_a_single_rule(): void
    {
        [$user, $org] = $this->makeTenant();

        $this->actingAs($user)->post(route('segments.store'), [
            'name' => 'Subscribed only',
            'rule_field' => ['status'],
            'rule_operator' => ['equals'],
            'rule_value' => ['subscribed'],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('segments', ['organization_id' => $org->id, 'name' => 'Subscribed only']);
    }

    public function test_an_invalid_operator_for_a_field_is_rejected(): void
    {
        [$user] = $this->makeTenant();

        $this->actingAs($user)->post(route('segments.store'), [
            'name' => 'Bad rule',
            'rule_field' => ['status'],
            'rule_operator' => ['has'], // "has" is a tag operator, not a status operator
            'rule_value' => ['subscribed'],
        ])->assertStatus(422);

        $this->assertDatabaseMissing('segments', ['name' => 'Bad rule']);
    }

    public function test_status_rule_matches_only_the_right_contacts(): void
    {
        [, $org] = $this->makeTenant();
        $subscribed = $this->makeContact($org, ['status' => 'subscribed']);
        $this->makeContact($org, ['status' => 'unsubscribed']);

        $segment = $org->segments()->create([
            'name' => 'Subscribed',
            'rules' => [['field' => 'status', 'operator' => 'equals', 'value' => 'subscribed']],
        ]);

        $matches = $segment->matchingContacts($org);

        $this->assertCount(1, $matches);
        $this->assertTrue($matches->contains('id', $subscribed->id));
    }

    public function test_tag_has_rule_matches_exact_tags_only(): void
    {
        [, $org] = $this->makeTenant();
        $vip = $this->makeContact($org, ['tags' => 'VIP, newsletter']);
        $this->makeContact($org, ['tags' => 'non-VIP']);
        $this->makeContact($org, ['tags' => null]);

        $segment = $org->segments()->create([
            'name' => 'VIP',
            'rules' => [['field' => 'tag', 'operator' => 'has', 'value' => 'vip']],
        ]);

        $matches = $segment->matchingContacts($org);

        $this->assertCount(1, $matches);
        $this->assertTrue($matches->contains('id', $vip->id));
    }

    public function test_tag_not_has_rule_excludes_matching_tags(): void
    {
        [, $org] = $this->makeTenant();
        $this->makeContact($org, ['tags' => 'vip']);
        $plain = $this->makeContact($org, ['tags' => 'newsletter']);

        $segment = $org->segments()->create([
            'name' => 'Not VIP',
            'rules' => [['field' => 'tag', 'operator' => 'not_has', 'value' => 'vip']],
        ]);

        $matches = $segment->matchingContacts($org);

        $this->assertCount(1, $matches);
        $this->assertTrue($matches->contains('id', $plain->id));
    }

    public function test_email_domain_rule_matches(): void
    {
        [, $org] = $this->makeTenant();
        $gmail = $this->makeContact($org, ['email' => 'person@gmail.com']);
        $this->makeContact($org, ['email' => 'person@yahoo.com']);

        $segment = $org->segments()->create([
            'name' => 'Gmail users',
            'rules' => [['field' => 'email_domain', 'operator' => 'equals', 'value' => 'gmail.com']],
        ]);

        $matches = $segment->matchingContacts($org);

        $this->assertCount(1, $matches);
        $this->assertTrue($matches->contains('id', $gmail->id));
    }

    public function test_created_at_rule_matches_by_date_added(): void
    {
        [, $org] = $this->makeTenant();
        $old = $this->makeContact($org);
        $recent = $this->makeContact($org);

        Contact::where('id', $old->id)->update(['created_at' => now()->subDays(60)]);
        Contact::where('id', $recent->id)->update(['created_at' => now()->subDays(1)]);

        $segment = $org->segments()->create([
            'name' => 'Recent contacts',
            'rules' => [['field' => 'created_at', 'operator' => 'after', 'value' => now()->subDays(30)->toDateString()]],
        ]);

        $matches = $segment->matchingContacts($org);

        $this->assertCount(1, $matches);
        $this->assertTrue($matches->contains('id', $recent->id));
    }

    public function test_multiple_rules_combine_with_and(): void
    {
        [, $org] = $this->makeTenant();
        $both = $this->makeContact($org, ['status' => 'subscribed', 'tags' => 'vip']);
        $this->makeContact($org, ['status' => 'subscribed', 'tags' => 'newsletter']);
        $this->makeContact($org, ['status' => 'unsubscribed', 'tags' => 'vip']);

        $segment = $org->segments()->create([
            'name' => 'Subscribed VIPs',
            'rules' => [
                ['field' => 'status', 'operator' => 'equals', 'value' => 'subscribed'],
                ['field' => 'tag', 'operator' => 'has', 'value' => 'vip'],
            ],
        ]);

        $matches = $segment->matchingContacts($org);

        $this->assertCount(1, $matches);
        $this->assertTrue($matches->contains('id', $both->id));
    }

    public function test_segments_are_tenant_isolated(): void
    {
        [$userA, $orgA] = $this->makeTenant('A');
        [, $orgB] = $this->makeTenant('B');

        $foreignSegment = $orgB->segments()->create([
            'name' => 'B segment',
            'rules' => [['field' => 'status', 'operator' => 'equals', 'value' => 'subscribed']],
        ]);

        $this->actingAs($userA)
            ->get(route('segments.edit', $foreignSegment->id))
            ->assertRedirect(route('segments.index'));
    }

    public function test_only_the_owner_can_delete_a_segment(): void
    {
        [, $org] = $this->makeTenant();
        $segment = $org->segments()->create([
            'name' => 'Whatever',
            'rules' => [['field' => 'status', 'operator' => 'equals', 'value' => 'subscribed']],
        ]);

        $member = $this->attachMember($org, 'editor');

        $this->actingAs($member)
            ->delete(route('segments.destroy', $segment->id))
            ->assertForbidden();

        $this->assertDatabaseHas('segments', ['id' => $segment->id]);
    }
}
