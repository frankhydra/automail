<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the organization metrics, performance dashboard, and charts.
     */
    public function index(): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        $empty = [
            'organizationName' => $user?->name ?? 'there',
            'totalContacts' => 0, 'subscribedContacts' => 0, 'newContactsThisMonth' => 0,
            'bouncedContacts' => 0, 'subscribedRate' => 0,
            'totalCampaigns' => 0, 'draftCampaigns' => 0, 'scheduledCampaigns' => 0,
            'totalDelivered' => 0, 'totalProcessed' => 0, 'deliveryRate' => 100,
            'openRate' => 0, 'clickRate' => 0, 'totalOpened' => 0, 'totalClicked' => 0,
            'audienceTarget' => 10, 'segmentCount' => 0,
            'recentCampaigns' => collect(),
            'chartDates' => [], 'sentData' => [], 'openData' => [], 'clickData' => [],
        ];

        if (!$organization) {
            return view('dashboard', $empty);
        }

        // A subquery (not a list of ids loaded into PHP): one cheap query at any size.
        $campaignIds = Campaign::where('organization_id', $organization->id)->select('id');

        // --- Contacts: one grouped query instead of several counts ---
        $contactRow = $organization->contacts()
            ->selectRaw("COUNT(*) as total,
                SUM(CASE WHEN status = 'subscribed' THEN 1 ELSE 0 END) as subscribed,
                SUM(CASE WHEN status = 'bounced' THEN 1 ELSE 0 END) as bounced,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as new_this_month", [Carbon::now()->startOfMonth()])
            ->first();

        $totalContacts = (int) ($contactRow->total ?? 0);
        $subscribedContacts = (int) ($contactRow->subscribed ?? 0);
        $bouncedContacts = (int) ($contactRow->bounced ?? 0);
        $newContactsThisMonth = (int) ($contactRow->new_this_month ?? 0);
        $subscribedRate = $totalContacts > 0 ? (int) round($subscribedContacts / $totalContacts * 100) : 0;

        // --- Campaign status counts: one grouped query ---
        $campaignStatuses = $organization->campaigns()->standalone()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalCampaigns = (int) $campaignStatuses->sum();
        $draftCampaigns = (int) ($campaignStatuses['draft'] ?? 0);
        $scheduledCampaigns = (int) ($campaignStatuses['scheduled'] ?? 0);

        // --- Delivery + engagement totals: ONE query over recipients ---
        $totals = CampaignRecipient::whereIn('campaign_id', $campaignIds)
            ->selectRaw("
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'sent' AND opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened,
                SUM(CASE WHEN status = 'sent' AND clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicked")
            ->first();

        $totalDelivered = (int) ($totals->sent ?? 0);
        $totalFailed = (int) ($totals->failed ?? 0);
        $totalOpened = (int) ($totals->opened ?? 0);
        $totalClicked = (int) ($totals->clicked ?? 0);
        $totalProcessed = $totalDelivered + $totalFailed;

        $deliveryRate = $totalProcessed > 0 ? round($totalDelivered / $totalProcessed * 100, 1) : 100;
        $openRate = $totalDelivered > 0 ? round($totalOpened / $totalDelivered * 100, 1) : 0;
        $clickRate = $totalDelivered > 0 ? round($totalClicked / $totalDelivered * 100, 1) : 0;

        // Next audience milestone to aim for (progress bar on the dashboard).
        $audienceTarget = collect([10, 25, 50, 100, 250, 500, 1000, 2500, 5000, 10000, 25000, 50000])
            ->first(fn ($goal) => $goal > $subscribedContacts) ?? ($subscribedContacts + 10000);

        $segmentCount = $organization->segments()->count();

        // --- Recent campaigns with per-campaign open rate ---
        $recentCampaigns = $organization->campaigns()->standalone()
            ->withCount([
                'recipients as total_recipients',
                'recipients as sent_recipients' => fn ($q) => $q->where('status', 'sent'),
                'recipients as opened_recipients' => fn ($q) => $q->where('status', 'sent')->whereNotNull('opened_at'),
            ])
            ->orderByDesc('created_at')
            ->take(4)
            ->get();

        // --- Chart data (last 7 days): one grouped query per metric ---
        $start = Carbon::today()->subDays(6)->startOfDay();
        $end = Carbon::today()->endOfDay();

        // $column is always one of three fixed names below, never user input.
        $countByDay = fn (string $column) => CampaignRecipient::whereIn('campaign_id', $campaignIds)
            ->whereBetween($column, [$start, $end])
            ->selectRaw("DATE({$column}) as day, COUNT(*) as total")
            ->groupByRaw("DATE({$column})")
            ->pluck('total', 'day');

        $sentByDay = $countByDay('sent_at');
        $opensByDay = $countByDay('opened_at');
        $clicksByDay = $countByDay('clicked_at');

        $chartDates = $sentData = $openData = $clickData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $key = $date->toDateString();

            $chartDates[] = $date->format('M d');
            $sentData[] = (int) ($sentByDay[$key] ?? 0);
            $openData[] = (int) ($opensByDay[$key] ?? 0);
            $clickData[] = (int) ($clicksByDay[$key] ?? 0);
        }

        return view('dashboard', [
            'organizationName' => $organization->name,
            'totalContacts' => $totalContacts,
            'subscribedContacts' => $subscribedContacts,
            'newContactsThisMonth' => $newContactsThisMonth,
            'bouncedContacts' => $bouncedContacts,
            'subscribedRate' => $subscribedRate,
            'totalCampaigns' => $totalCampaigns,
            'draftCampaigns' => $draftCampaigns,
            'scheduledCampaigns' => $scheduledCampaigns,
            'totalDelivered' => $totalDelivered,
            'totalProcessed' => $totalProcessed,
            'deliveryRate' => $deliveryRate,
            'openRate' => $openRate,
            'clickRate' => $clickRate,
            'totalOpened' => $totalOpened,
            'totalClicked' => $totalClicked,
            'audienceTarget' => $audienceTarget,
            'segmentCount' => $segmentCount,
            'recentCampaigns' => $recentCampaigns,
            'chartDates' => $chartDates,
            'sentData' => $sentData,
            'openData' => $openData,
            'clickData' => $clickData,
        ]);
    }
}
