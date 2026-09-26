<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    // --- Campaigns: owner/admin/manager/editor can create; viewer cannot ---

    public function test_a_viewer_cannot_create_a_campaign(): void
    {
        [, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org);
        $this->makeContact($org);
        $viewer = $this->attachMember($org, 'viewer');

        $this->actingAs($viewer)->post(route('campaigns.store'), [
            'name' => 'X', 'subject' => 'X', 'body' => 'X', 'sending_identity_id' => $identity->id,
        ])->assertForbidden();
    }

    public function test_an_editor_can_create_a_campaign(): void
    {
        [, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org);
        $this->makeContact($org);
        $editor = $this->attachMember($org, 'editor');

        $this->actingAs($editor)->post(route('campaigns.store'), [
            'name' => 'X', 'subject' => 'X', 'body' => 'X', 'sending_identity_id' => $identity->id,
        ])->assertSessionHasNoErrors();
    }

    public function test_a_viewer_cannot_dispatch_or_cancel_a_campaign(): void
    {
        [, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org);
        $contact = $this->makeContact($org);
        $campaign = $this->makeCampaign($org, $identity, [$contact]);
        $viewer = $this->attachMember($org, 'viewer');

        $this->actingAs($viewer)->post(route('campaigns.dispatch', $campaign->id))->assertForbidden();

        $campaign->update(['status' => 'scheduled', 'scheduled_at' => now()->addHour()]);
        $this->actingAs($viewer)->post(route('campaigns.cancel', $campaign->id))->assertForbidden();
    }

    // --- Contacts: manager can edit; editor/viewer cannot ---

    public function test_a_manager_can_update_a_contact_but_an_editor_cannot(): void
    {
        [, $org] = $this->makeTenant();
        $contact = $this->makeContact($org);
        $manager = $this->attachMember($org, 'manager');
        $editor = $this->attachMember($org, 'editor');

        $this->actingAs($manager)->put(route('contacts.update', $contact->id), [
            'email' => $contact->email, 'status' => 'subscribed',
        ])->assertSessionHasNoErrors();

        $this->actingAs($editor)->put(route('contacts.update', $contact->id), [
            'email' => $contact->email, 'status' => 'subscribed',
        ])->assertForbidden();
    }

    // --- Segments: manager can create; editor cannot ---

    public function test_a_manager_can_create_a_segment_but_an_editor_cannot(): void
    {
        [, $org] = $this->makeTenant();
        $manager = $this->attachMember($org, 'manager');
        $editor = $this->attachMember($org, 'editor');

        $this->actingAs($manager)->post(route('segments.store'), [
            'name' => 'Test', 'rule_field' => ['status'], 'rule_operator' => ['equals'], 'rule_value' => ['subscribed'],
        ])->assertSessionHasNoErrors();

        $this->actingAs($editor)->post(route('segments.store'), [
            'name' => 'Test2', 'rule_field' => ['status'], 'rule_operator' => ['equals'], 'rule_value' => ['subscribed'],
        ])->assertForbidden();
    }

    // --- Templates: editor can create (spec's "Editor -> campaign editing") ---

    public function test_an_editor_can_create_a_template_but_a_viewer_cannot(): void
    {
        [, $org] = $this->makeTenant();
        $editor = $this->attachMember($org, 'editor');
        $viewer = $this->attachMember($org, 'viewer');

        $this->actingAs($editor)->post(route('templates.store'), [
            'name' => 'T', 'body' => '<p>hi</p>',
        ])->assertSessionHasNoErrors();

        $this->actingAs($viewer)->post(route('templates.store'), [
            'name' => 'T2', 'body' => '<p>hi</p>',
        ])->assertForbidden();
    }

    // --- Sending identities: manager cannot add a sender; only owner/admin can ---

    public function test_a_manager_cannot_add_a_sending_identity_but_an_admin_can(): void
    {
        [, $org] = $this->makeTenant();
        $manager = $this->attachMember($org, 'manager');
        $admin = $this->attachMember($org, 'admin');

        $this->actingAs($manager)->post(route('sending-identities.store'), [
            'from_name' => 'X', 'from_email' => 'x@example.com',
        ])->assertForbidden();

        $this->actingAs($admin)->post(route('sending-identities.store'), [
            'from_name' => 'X', 'from_email' => 'x2@example.com',
        ])->assertSessionHasNoErrors();
    }

    // --- Deletion: widened from owner-only to owner-or-admin ---

    public function test_an_admin_can_now_delete_a_campaign_a_manager_still_cannot(): void
    {
        [, $org] = $this->makeTenant();
        $identity = $this->makeIdentity($org);
        $contact = $this->makeContact($org);
        $campaign = $this->makeCampaign($org, $identity, [$contact]);
        $admin = $this->attachMember($org, 'admin');
        $manager = $this->attachMember($org, 'manager');

        $this->actingAs($manager)->delete(route('campaigns.destroy', $campaign->id))->assertForbidden();
        $this->actingAs($admin)->delete(route('campaigns.destroy', $campaign->id))->assertRedirect(route('campaigns.index'));

        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
    }
}
