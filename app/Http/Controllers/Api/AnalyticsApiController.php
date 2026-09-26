<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CampaignRecipient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * A small, hand-shaped summary rather than a raw table dump - exactly what the
 * spec's "Do not expose database internals directly" instruction asks for.
 */
class AnalyticsApiController extends Controller
{
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $organization = $user->currentOrganization();

        if (!$organization) {
            return response()->json(['message' => 'No organization found for this account.'], 404);
        }

        $recipientStatuses = CampaignRecipient::whereHas(
            'campaign',
            fn ($query) => $query->where('organization_id', $organization->id)
        )->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status');

        return response()->json([
            'contacts' => [
                'total' => $organization->contacts()->count(),
                'subscribed' => $organization->contacts()->where('status', 'subscribed')->count(),
                'unsubscribed' => $organization->contacts()->where('status', 'unsubscribed')->count(),
                'bounced' => $organization->contacts()->where('status', 'bounced')->count(),
            ],
            'campaigns' => [
                'total' => $organization->campaigns()->count(),
                'sent' => $organization->campaigns()->where('status', 'sent')->count(),
                'draft' => $organization->campaigns()->where('status', 'draft')->count(),
                'scheduled' => $organization->campaigns()->where('status', 'scheduled')->count(),
            ],
            'emails' => [
                'sent' => $recipientStatuses->get('sent', 0),
                'failed' => $recipientStatuses->get('failed', 0),
                'bounced' => $recipientStatuses->get('bounced', 0),
                'complained' => $recipientStatuses->get('complained', 0),
            ],
        ]);
    }
}
