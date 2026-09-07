<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCampaignDispatchJob;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CampaignController extends Controller
{
    /**
     * Display a listing of email campaigns for the current organization.
     */
    public function index(): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        $campaigns = $organization
            ? $organization->campaigns()->with('sendingIdentity')->withCount('recipients')->latest()->get()
            : collect();

        return view('campaigns.index', compact('campaigns'));
    }

    /**
     * Show the campaign creation form.
     */
    public function create(): View|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        if (!$organization) {
            return redirect()->route('dashboard')->withErrors(['error' => 'No active organization found.']);
        }

        // Fetch verified sending identities
        $identities = $organization->sendingIdentities()->where('verification_status', 'verified')->get();
        if ($identities->isEmpty()) {
            return redirect()->route('sending-identities.index')
                ->withErrors(['error' => 'You must have at least one verified Sending Identity before creating a campaign.']);
        }

        $templates = $organization->templates()->latest()->get();
        $contactLists = $organization->contactLists()->get();
        $totalContacts = $organization->contacts()->where('status', 'subscribed')->count();

        return view('campaigns.create', compact('identities', 'templates', 'contactLists', 'totalContacts'));
    }

    /**
     * Store a newly created campaign and snapshot target recipients.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sending_identity_id' => 'required|exists:sending_identities,id',
            'template_id' => 'nullable|exists:templates,id',
            'contact_list_id' => 'nullable|exists:contact_lists,id',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        if (!$organization) {
            return redirect()->back()->withErrors(['error' => 'No active organization found.']);
        }

        // Ensure sending identity belongs to organization and is verified
        $identity = $organization->sendingIdentities()
            ->where('id', $request->sending_identity_id)
            ->where('verification_status', 'verified')
            ->first();

        if (!$identity) {
            return redirect()->back()->withErrors(['sending_identity_id' => 'Selected sending identity is invalid or not verified.']);
        }

        // Determine target contacts
        if ($request->filled('contact_list_id')) {
            $list = $organization->contactLists()->find($request->contact_list_id);
            $contacts = $list ? $list->contacts()->where('status', 'subscribed')->get() : collect();
        } else {
            // Target all subscribed contacts in the organization
            $contacts = $organization->contacts()->where('status', 'subscribed')->get();
        }

        if ($contacts->isEmpty()) {
            return redirect()->back()->withErrors(['contact_list_id' => 'No subscribed contacts found for the selected audience target.']);
        }

        DB::transaction(function () use ($organization, $identity, $request, $contacts) {
            $campaign = $organization->campaigns()->create([
                'sending_identity_id' => $identity->id,
                'template_id' => $request->template_id,
                'name' => trim($request->name),
                'subject' => trim($request->subject),
                'body' => $request->body,
                'status' => 'draft',
            ]);

            // Create campaign recipient snapshots
            $recipientData = [];
            $now = now();
            foreach ($contacts as $contact) {
                $recipientData[] = [
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contact->id,
                    'status' => 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('campaign_recipients')->insert($recipientData);
        });

        return redirect()->route('campaigns.index')
            ->with('status', 'Campaign created and audience snapshotted successfully!');
    }

    /**
     * Display campaign performance details & recipient status breakdown.
     */
    public function show(int $id): View|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        $campaign = $organization
            ? $organization->campaigns()->with(['sendingIdentity', 'recipients.contact'])->find($id)
            : null;

        if (!$campaign) {
            return redirect()->route('campaigns.index')->withErrors(['error' => 'Campaign not found.']);
        }

        return view('campaigns.show', compact('campaign'));
    }

    /**
     * Dispatch the campaign via background queue worker.
     */
    public function dispatch(int $id): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        $campaign = $organization
            ? $organization->campaigns()->find($id)
            : null;

        if (!$campaign) {
            return redirect()->route('campaigns.index')->withErrors(['error' => 'Campaign not found.']);
        }

        if ($campaign->status !== 'draft') {
            return redirect()->back()->withErrors(['error' => 'Campaign has already been dispatched or queued.']);
        }

        $campaign->update(['status' => 'queued']);

        ProcessCampaignDispatchJob::dispatch($campaign->id);

        return redirect()->route('campaigns.show', $campaign->id)
            ->with('status', 'Campaign dispatch job queued successfully! Background workers are processing deliveries.');
    }

    /**
     * Delete a draft or completed campaign.
     */
    public function destroy(int $id): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        if ($organization) {
            $organization->campaigns()->where('id', $id)->delete();
        }

        return redirect()->route('campaigns.index')
            ->with('status', 'Campaign deleted successfully.');
    }
}