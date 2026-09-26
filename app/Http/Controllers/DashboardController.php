<?php

namespace App\Http\Controllers;

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

        if (!$organization) {
            return view('dashboard', [
                'totalContacts' => 0,
                'totalCampaigns' => 0,
                'totalDelivered' => 0,
                'deliveryRate' => 100,
                'recentCampaigns' => collect(),
                'chartDates' => [],
                'sentData' => [],
                'openData' => [],
                'clickData' => [],
            ]);
        }

        $campaignIds = $organization->campaigns()->pluck('id');
        $totalContacts = $organization->contacts()->where('status', 'subscribed')->count();
        $totalCampaigns = $organization->campaigns()->count();

        // Overall Stats
        $totalDelivered = CampaignRecipient::whereIn('campaign_id', $campaignIds)
            ->where('status', 'sent')
            ->count();

        $totalFailed = CampaignRecipient::whereIn('campaign_id', $campaignIds)
            ->where('status', 'failed')
            ->count();

        $totalProcessed = $totalDelivered + $totalFailed;
        $deliveryRate = $totalProcessed > 0
            ? round(($totalDelivered / $totalProcessed) * 100, 1)
            : 100;

        // Recent Campaigns Table
        $recentCampaigns = $organization->campaigns()
            ->with('sendingIdentity')
            ->withCount([
                'recipients as total_recipients',
                'recipients as sent_recipients' => function ($query) {
                    $query->where('status', 'sent');
                }
            ])
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        // --- CHART DATA GENERATION (Last 7 Days) ---
        $chartDates = [];
        $sentData = [];
        $openData = [];
        $clickData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartDates[] = $date->format('M d'); // e.g. "Sep 21"

            $sentData[] = CampaignRecipient::whereIn('campaign_id', $campaignIds)
                ->whereDate('sent_at', $date)
                ->count();

            $openData[] = CampaignRecipient::whereIn('campaign_id', $campaignIds)
                ->whereDate('opened_at', $date)
                ->count();

            $clickData[] = CampaignRecipient::whereIn('campaign_id', $campaignIds)
                ->whereDate('clicked_at', $date)
                ->count();
        }

        return view('dashboard', compact(
            'totalContacts',
            'totalCampaigns',
            'totalDelivered',
            'deliveryRate',
            'recentCampaigns',
            'chartDates',
            'sentData',
            'openData',
            'clickData'
        ));
    }
}