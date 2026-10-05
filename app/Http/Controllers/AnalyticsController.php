<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use App\Models\LinkClick;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Per-campaign performance report. Every role can read it (the spec gives
 * "Viewer -> analytics"), and every query is scoped to the current organization.
 */
class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        abort_if($organization === null, 403, 'No active organization found for your account.');

        $campaigns = $organization->campaigns()
            ->whereIn('status', ['sent', 'sending'])
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'status', 'sent_at']);

        // The ?campaign= id only counts if it is one of THIS organization's campaigns.
        $campaign = $campaigns->firstWhere('id', (int) $request->input('campaign')) ?? $campaigns->first();

        if (!$campaign) {
            return view('analytics.index', ['campaigns' => $campaigns, 'campaign' => null]);
        }

        $totals = CampaignRecipient::where('campaign_id', $campaign->id)
            ->selectRaw("
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status IN ('sent', 'failed', 'bounced', 'complained') THEN 1 ELSE 0 END) as attempted,
                SUM(CASE WHEN status = 'sent' AND opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened,
                SUM(CASE WHEN status = 'sent' AND clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicked,
                SUM(CASE WHEN bounced_at IS NOT NULL THEN 1 ELSE 0 END) as bounced,
                SUM(CASE WHEN complained_at IS NOT NULL THEN 1 ELSE 0 END) as complained")
            ->first();

        $sent = (int) ($totals->sent ?? 0);
        $attempted = (int) ($totals->attempted ?? 0);
        $opened = (int) ($totals->opened ?? 0);
        $clicked = (int) ($totals->clicked ?? 0);

        // Recipients of this campaign whose contact is now unsubscribed.
        $unsubscribed = CampaignRecipient::where('campaign_recipients.campaign_id', $campaign->id)
            ->join('contacts', 'contacts.id', '=', 'campaign_recipients.contact_id')
            ->where('contacts.status', 'unsubscribed')
            ->count();

        // Opens and clicks per hour for the first 24 hours after sending.
        $opensByHour = array_fill(0, 24, 0);
        $clicksByHour = array_fill(0, 24, 0);

        CampaignRecipient::where('campaign_id', $campaign->id)
            ->where('status', 'sent')
            ->whereNotNull('sent_at')
            ->where(fn ($q) => $q->whereNotNull('opened_at')->orWhereNotNull('clicked_at'))
            ->select(['sent_at', 'opened_at', 'clicked_at'])
            ->cursor()
            ->each(function ($row) use (&$opensByHour, &$clicksByHour) {
                if ($row->opened_at) {
                    $hour = (int) floor(($row->opened_at->getTimestamp() - $row->sent_at->getTimestamp()) / 3600);
                    if ($hour >= 0 && $hour < 24) {
                        $opensByHour[$hour]++;
                    }
                }

                if ($row->clicked_at) {
                    $hour = (int) floor(($row->clicked_at->getTimestamp() - $row->sent_at->getTimestamp()) / 3600);
                    if ($hour >= 0 && $hour < 24) {
                        $clicksByHour[$hour]++;
                    }
                }
            });

        $clients = CampaignRecipient::where('campaign_id', $campaign->id)
            ->where('status', 'sent')->whereNotNull('opened_at')
            ->selectRaw('open_client, COUNT(*) as total')
            ->groupBy('open_client')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->open_client ?: 'Unknown',
                'total' => (int) $row->total,
                'percent' => $opened > 0 ? (int) round($row->total / $opened * 100) : 0,
            ]);

        $topLinks = LinkClick::where('campaign_id', $campaign->id)
            ->selectRaw('url, COUNT(*) as clicks')
            ->groupBy('url')
            ->orderByDesc('clicks')
            ->limit(5)
            ->get();

        return view('analytics.index', [
            'campaigns' => $campaigns,
            'campaign' => $campaign,
            'sent' => $sent,
            'deliveryRate' => $attempted > 0 ? round($sent / $attempted * 100, 1) : 0,
            'opened' => $opened,
            'openRate' => $sent > 0 ? round($opened / $sent * 100, 1) : 0,
            'clicked' => $clicked,
            'clickToOpen' => $opened > 0 ? round($clicked / $opened * 100, 1) : 0,
            'unsubscribed' => $unsubscribed,
            'unsubscribeRate' => $sent > 0 ? round($unsubscribed / $sent * 100, 2) : 0,
            'bounced' => (int) ($totals->bounced ?? 0),
            'complained' => (int) ($totals->complained ?? 0),
            'chart' => [
                'labels' => array_map(fn ($h) => ($h + 1).'h', range(0, 23)),
                'opens' => $opensByHour,
                'clicks' => $clicksByHour,
            ],
            'clients' => $clients,
            'topLinks' => $topLinks,
            'timezone' => config('app.timezone'),
        ]);
    }
}
