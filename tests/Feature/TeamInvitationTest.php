<?php

namespace Tests\Feature;

use App\Mail\TeamInvitationMail;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class TeamInvitationTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_owner_can_invite_a_new_teammate_by_email(): void
    {
        Mail::fake();
        [$owner, $org] = $this->makeTenant();

        $this->actingAs($owner)->post(route('team.invite'), [
            'email' => 'new@example.com',
            'role' => 'editor',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('organization_invitations', [
            'organization_id' => $org->id,
            'email' => 'new@example.com',
            'role' => 'editor',
        ]);

        Mail::assertSent(TeamInvitationMail::class, fn ($mail) => $mail->hasTo('new@example.com'));
    }

    public function test_cannot_invite_someone_as_owner(): void
    {
        [$owner] = $this->makeTenant();

        $this->actingAs($owner)->post(route('team.invite'), [
            'email' => 'new@example.com',
            'role' => 'owner',
        ])->assertSessionHasErrors('role');
    }

    public function test_a_manager_cannot_send_invitations(): void
    {
        [, $org] = $this->makeTenant();
        $manager = $this->attachMember($org, 'manager');

        $this->actingAs($manager)->post(route('team.invite'), [
            'email' => 'new@example.com',
            'role' => 'editor',
        ])->assertForbidden();
    }

    public function test_an_admin_can_send_invitations(): void
    {
        Mail::fake();
        [, $org] = $this->makeTenant();
        $admin = $this->attachMember($org, 'admin');

        $this->actingAs($admin)->post(route('team.invite'), [
            'email' => 'new@example.com',
            'role' => 'viewer',
        ])->assertSessionHasNoErrors();
    }

    public function test_accepting_an_invite_as_a_brand_new_user_creates_an_account_and_joins_the_org_without_a_new_default_org(): void
    {
        Mail::fake();
        [$owner, $org] = $this->makeTenant();

        $this->actingAs($owner)->post(route('team.invite'), ['email' => 'joiner@example.com', 'role' => 'viewer']);
        $invitation = OrganizationInvitation::where('email', 'joiner@example.com')->first();

        $acceptUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'invitations.accept', now()->addDays(7), ['token' => $invitation->token]
        );

        $this->get($acceptUrl)->assertOk();

        $this->post(route('invitations.register', $invitation->token), [
            'name' => 'New Joiner',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $user = User::where('email', 'joiner@example.com')->firstOrFail();

        $this->assertSame(1, $user->organizations()->count());
        $this->assertSame($org->id, $user->currentOrganization()->id);
        $this->assertSame('viewer', $user->currentRole());
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_an_expired_invitation_cannot_be_accepted(): void
    {
        Mail::fake();
        [$owner, $org] = $this->makeTenant();

        $this->actingAs($owner)->post(route('team.invite'), ['email' => 'late@example.com', 'role' => 'viewer']);
        $invitation = OrganizationInvitation::where('email', 'late@example.com')->first();
        // Model::update() won't touch created_at (deliberately not fillable - see
        // OrganizationInvitation), so force it directly for this test.
        $invitation->created_at = now()->subDays(10);
        $invitation->save();

        $acceptUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'invitations.accept', now()->addDays(7), ['token' => $invitation->token]
        );

        $this->get($acceptUrl)->assertRedirect(route('login'));
    }

    public function test_only_the_owner_can_change_a_members_role(): void
    {
        [$owner, $org] = $this->makeTenant();
        $admin = $this->attachMember($org, 'admin');
        $editor = $this->attachMember($org, 'editor');

        $this->actingAs($admin)
            ->patch(route('team.members.role', $editor->id), ['role' => 'manager'])
            ->assertForbidden();

        $this->actingAs($owner)
            ->patch(route('team.members.role', $editor->id), ['role' => 'manager'])
            ->assertRedirect(route('team.index'));

        $this->assertSame('manager', $org->users()->where('users.id', $editor->id)->first()->pivot->role);
    }

    public function test_the_owners_role_cannot_be_changed(): void
    {
        [$owner, $org] = $this->makeTenant();

        $this->actingAs($owner)
            ->patch(route('team.members.role', $owner->id), ['role' => 'admin'])
            ->assertSessionHasErrors('error');

        $this->assertSame('owner', $org->users()->where('users.id', $owner->id)->first()->pivot->role);
    }

    public function test_the_owner_cannot_be_removed(): void
    {
        [$owner, $org] = $this->makeTenant();

        $this->actingAs($owner)
            ->delete(route('team.members.remove', $owner->id))
            ->assertSessionHasErrors('error');

        $this->assertTrue($org->users()->where('users.id', $owner->id)->exists());
    }

    public function test_the_owner_can_remove_a_member(): void
    {
        [$owner, $org] = $this->makeTenant();
        $member = $this->attachMember($org, 'viewer');

        $this->actingAs($owner)
            ->delete(route('team.members.remove', $member->id))
            ->assertRedirect(route('team.index'));

        $this->assertFalse($org->users()->where('users.id', $member->id)->exists());
    }
}
