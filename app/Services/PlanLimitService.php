<?php

namespace App\Services;

use App\Models\CampaignRecipient;
use App\Models\Organization;

/**
 * The one place plan limits are read and checked. Every enforcement point
 * (contact import, team invites, sending identities, campaign dispatch) calls
 * this rather than reading config/plans.php or counting rows itself, so the
 * rules can never drift between them.
 */
class PlanLimitService
{
    /**
     * @return array{contacts: ?int, monthly_emails: ?int, team_members: ?int, sending_identities: ?int}
     */
    public function limits(Organization $organization): array
    {
        $plan = config('plans.'.$organization->plan) ?? config('plans.free');

        return $plan['limits'];
    }

    public function planName(Organization $organization): string
    {
        $plan = config('plans.'.$organization->plan) ?? config('plans.free');

        return $plan['name'];
    }

    public function contactsUsed(Organization $organization): int
    {
        return $organization->contacts()->count();
    }

    public function teamMembersUsed(Organization $organization): int
    {
        // Pending invitations count too - otherwise someone could invite past the
        // limit and only get blocked once every invite has already been accepted.
        return $organization->users()->count() + $organization->invitations()->pending()->count();
    }

    public function sendingIdentitiesUsed(Organization $organization): int
    {
        return $organization->sendingIdentities()->count();
    }

    public function monthlyEmailsUsed(Organization $organization): int
    {
        return CampaignRecipient::whereHas(
            'campaign',
            fn ($query) => $query->where('organization_id', $organization->id)
        )
            ->where('status', 'sent')
            ->whereBetween('sent_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }

    public function canAddContacts(Organization $organization, int $additional = 1): bool
    {
        $limit = $this->limits($organization)['contacts'];

        return $limit === null || ($this->contactsUsed($organization) + $additional) <= $limit;
    }

    public function canInviteTeamMember(Organization $organization): bool
    {
        $limit = $this->limits($organization)['team_members'];

        return $limit === null || $this->teamMembersUsed($organization) < $limit;
    }

    public function canAddSendingIdentity(Organization $organization): bool
    {
        $limit = $this->limits($organization)['sending_identities'];

        return $limit === null || $this->sendingIdentitiesUsed($organization) < $limit;
    }

    public function canSendEmails(Organization $organization, int $additional): bool
    {
        $limit = $this->limits($organization)['monthly_emails'];

        return $limit === null || ($this->monthlyEmailsUsed($organization) + $additional) <= $limit;
    }

    /**
     * How many more contacts can be added before hitting the plan's limit.
     * Returns PHP_INT_MAX for an unlimited plan, so callers can compare
     * without a separate null check.
     */
    public function remainingContactSlots(Organization $organization): int
    {
        $limit = $this->limits($organization)['contacts'];

        return $limit === null ? PHP_INT_MAX : max(0, $limit - $this->contactsUsed($organization));
    }

    /**
     * A shaped summary for the Billing page: each category's used/limit/percent.
     *
     * @return array<string, array{used: int, limit: ?int, percent: ?int}>
     */
    public function usageSummary(Organization $organization): array
    {
        $limits = $this->limits($organization);

        $categories = [
            'contacts' => $this->contactsUsed($organization),
            'monthly_emails' => $this->monthlyEmailsUsed($organization),
            'team_members' => $this->teamMembersUsed($organization),
            'sending_identities' => $this->sendingIdentitiesUsed($organization),
        ];

        $summary = [];
        foreach ($categories as $key => $used) {
            $limit = $limits[$key];
            $summary[$key] = [
                'used' => $used,
                'limit' => $limit,
                'percent' => $limit === null || $limit === 0 ? null : (int) min(100, round(($used / $limit) * 100)),
            ];
        }

        return $summary;
    }
}
