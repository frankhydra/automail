<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the organization metrics and performance dashboard.
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
            ]);
        }

        $campaignIds = $organization->campaigns()->pluck('id');

        $totalContacts = $organization->contacts()->where('status', 'subscribed')->count();
        $totalCampaigns = $organization->campaigns()->count();

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

        $recentCampaigns = $organization->campaigns()
            ->with('sendingIdentity')
            ->withCount([
                'recipients as total_recipients',
                'recipients as sent_recipients' => function ($query) {
                    $query->where('status', 'sent');
                }
            ])
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'totalContacts',
            'totalCampaigns',
            'totalDelivered',
            'deliveryRate',
            'recentCampaigns'
        ));
    }
}