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

        if (!$organization) {
            return view('dashboard', [
                'totalContacts' => 0,
                'totalCampaigns' => 0,
                'totalDelivered' => 0,
                'totalProcessed' => 0,
                'deliveryRate' => 100,
                'recentCampaigns' => collect(),
                'chartDates' => [],
                'sentData' => [],
                'openData' => [],
                'clickData' => [],
            ]);
        }

        // A subquery (not a list of ids loaded into PHP), so it stays one cheap
        // query no matter how many campaigns the organization has.
        $campaignIds = Campaign::where('organization_id', $organization->id)->select('id');

        $totalContacts = $organization->contacts()->where('status', 'subscribed')->count();
        $totalCampaigns = $organization->campaigns()->count();

        // Sent + failed counts in a single grouped query instead of one query each.
        $statusCounts = CampaignRecipient::whereIn('campaign_id', $campaignIds)
            ->whereIn('status', ['sent', 'failed'])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalDelivered = (int) ($statusCounts['sent'] ?? 0);
        $totalFailed = (int) ($statusCounts['failed'] ?? 0);
        $totalProcessed = $totalDelivered + $totalFailed;
        $deliveryRate = $totalProcessed > 0
            ? round(($totalDelivered / $totalProcessed) * 100, 1)
            : 100;

        // Recent Campaigns list
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

        // --- CHART DATA (last 7 days) ---
        // One grouped query per metric (3 total) rather than one query per metric
        // per day (21 total) - the main reason this page used to feel slow.
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

        $chartDates = [];
        $sentData = [];
        $openData = [];
        $clickData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $key = $date->toDateString();

            $chartDates[] = $date->format('M d'); // e.g. "Sep 21"
            $sentData[] = (int) ($sentByDay[$key] ?? 0);
            $openData[] = (int) ($opensByDay[$key] ?? 0);
            $clickData[] = (int) ($clicksByDay[$key] ?? 0);
        }

        return view('dashboard', compact(
            'totalContacts',
            'totalCampaigns',
            'totalDelivered',
            'totalProcessed',
            'deliveryRate',
            'recentCampaigns',
            'chartDates',
            'sentData',
            'openData',
            'clickData'
        ));
    }
}
