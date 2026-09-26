<?php

namespace App\Http\Controllers;

use App\Mail\TeamInvitationMail;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Services\PlanLimitService;
use App\Support\EnsuresTeamPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeamController extends Controller
{
    use EnsuresTeamPermission;

    public function __construct(private readonly PlanLimitService $planLimits)
    {
    }

    public function index(): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        $members = $organization ? $organization->users()->get() : collect();
        $invitations = $organization ? $organization->invitations()->pending()->latest()->get() : collect();

        return view('team.index', [
            'members' => $members,
            'invitations' => $invitations,
            'roles' => OrganizationInvitation::invitableRoles(),
            'currentUserId' => $user?->id,
        ]);
    }

    public function invite(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManage($user);
        $organization = $user->currentOrganization();

        $data = $request->validate([
            'email' => 'required|email|max:255',
            'role' => ['required', Rule::in(OrganizationInvitation::invitableRoles())],
        ]);

        $email = strtolower(trim($data['email']));

        if ($email === strtolower($user->email)) {
            return redirect()->back()->withErrors(['email' => "You can't invite yourself."]);
        }

        if ($organization->users()->where('email', $email)->exists()) {
            return redirect()->back()->withErrors(['email' => 'That person is already a member of this organization.']);
        }

        if ($organization->invitations()->pending()->where('email', $email)->exists()) {
            return redirect()->back()->withErrors(['email' => 'There is already a pending invitation for that email. Use Resend instead.']);
        }

        if (!$this->planLimits->canInviteTeamMember($organization)) {
            $limit = $this->planLimits->limits($organization)['team_members'];

            return redirect()->back()->withErrors([
                'email' => "Your plan allows up to {$limit} team member(s) (including pending invites). Upgrade your plan to invite more.",
            ]);
        }

        $invitation = $organization->invitations()->create([
            'email' => $email,
            'role' => $data['role'],
            'token' => Str::random(48),
            'invited_by' => $user->id,
        ]);

        $this->sendInvitationEmail($invitation);

        return redirect()->route('team.index')->with('status', "Invitation sent to {$email}.");
    }

    public function resend(int $invitationId): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManage($user);
        $organization = $user->currentOrganization();

        $invitation = $organization ? $organization->invitations()->pending()->find($invitationId) : null;

        if (!$invitation) {
            return redirect()->route('team.index')->withErrors(['error' => 'Invitation not found.']);
        }

        // A fresh token means a resend also resets the 7-day expiry window.
        // Direct property assignment (not update()): created_at is deliberately
        // NOT in $fillable, so it can never be set from request input.
        $invitation->token = Str::random(48);
        $invitation->created_at = now();
        $invitation->save();

        $this->sendInvitationEmail($invitation);

        return redirect()->route('team.index')->with('status', "Invitation resent to {$invitation->email}.");
    }

    public function cancelInvite(int $invitationId): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManage($user);
        $organization = $user->currentOrganization();

        if ($organization) {
            $organization->invitations()->pending()->where('id', $invitationId)->delete();
        }

        return redirect()->route('team.index')->with('status', 'Invitation cancelled.');
    }

    public function updateRole(Request $request, int $memberId): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureOwner($user);
        $organization = $user->currentOrganization();

        $data = $request->validate([
            'role' => ['required', Rule::in(OrganizationInvitation::invitableRoles())],
        ]);

        $member = $organization ? $organization->users()->where('users.id', $memberId)->first() : null;

        if (!$member) {
            return redirect()->route('team.index')->withErrors(['error' => 'Member not found.']);
        }

        if ($member->pivot->role === 'owner') {
            return redirect()->route('team.index')->withErrors(['error' => "The organization owner's role can't be changed here."]);
        }

        $organization->users()->updateExistingPivot($member->id, ['role' => $data['role']]);

        return redirect()->route('team.index')->with('status', "{$member->name}'s role updated to ".ucfirst($data['role']).'.');
    }

    public function removeMember(int $memberId): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureOwner($user);
        $organization = $user->currentOrganization();

        $member = $organization ? $organization->users()->where('users.id', $memberId)->first() : null;

        if (!$member) {
            return redirect()->route('team.index')->withErrors(['error' => 'Member not found.']);
        }

        if ($member->pivot->role === 'owner') {
            return redirect()->route('team.index')->withErrors(['error' => "The organization owner can't be removed."]);
        }

        $organization->users()->detach($member->id);

        return redirect()->route('team.index')->with('status', "{$member->name} removed from the organization.");
    }

    protected function sendInvitationEmail(OrganizationInvitation $invitation): void
    {
        $acceptUrl = URL::temporarySignedRoute(
            'invitations.accept',
            now()->addDays(7),
            ['token' => $invitation->token]
        );

        Mail::to($invitation->email)->send(new TeamInvitationMail($invitation, $acceptUrl));
    }
}
