<?php

namespace App\Support;

use App\Models\User;

/**
 * Role checks for a five-role team: owner, admin, manager, editor, viewer.
 * Replaces the old app/Support/EnsuresOwnerRole.php - delete that file, nothing
 * uses it after this milestone. See the class-level doc on each method for
 * exactly which roles it allows and why.
 */
trait EnsuresTeamPermission
{
    /**
     * Owner or Admin only. For destructive/administrative actions: deleting a
     * campaign, contact, template, segment, or sending identity, and managing
     * the team itself (inviting people).
     */
    protected function ensureCanManage(?User $user): void
    {
        abort_unless($user && in_array($user->currentRole(), ['owner', 'admin'], true), 403, 'Only an owner or admin can do this.');
    }

    /**
     * Owner, Admin, or Manager. For creating/editing contacts, contact lists,
     * segments, and sending identities - the spec's "Manager -> contacts" line.
     */
    protected function ensureCanManageContacts(?User $user): void
    {
        abort_unless($user && in_array($user->currentRole(), ['owner', 'admin', 'manager'], true), 403, 'You do not have permission to manage contacts.');
    }

    /**
     * Owner, Admin, Manager, or Editor. For creating/editing/dispatching
     * campaigns and templates - the spec's "Editor -> campaign editing" line.
     * This is the only tier a Viewer never reaches.
     */
    protected function ensureCanEditCampaigns(?User $user): void
    {
        abort_unless($user && in_array($user->currentRole(), ['owner', 'admin', 'manager', 'editor'], true), 403, 'You do not have permission to edit campaigns.');
    }

    /**
     * Owner only. Reserved for changing a teammate's role or removing them -
     * kept out of Admin's reach to avoid a non-Owner granting themselves or
     * someone else more access than the Owner intended.
     */
    protected function ensureOwner(?User $user): void
    {
        abort_unless($user && $user->currentRole() === 'owner', 403, 'Only the organization owner can do this.');
    }
}
