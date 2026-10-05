<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\User;
use App\Services\CampaignDispatchService;
use App\Support\EnsuresTeamPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CampaignController extends Controller
{
    use EnsuresTeamPermission;

    /**
     * Display a listing of email campaigns for the current organization.
     */
    public function index(): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        $campaigns = $organization
            ? $organization->campaigns()->standalone()
                ->with('sendingIdentity')
                ->withCount([
                    'recipients',
                    'recipients as sent_recipients' => fn ($q) => $q->where('status', 'sent'),
                    'recipients as opened_recipients' => fn ($q) => $q->where('status', 'sent')->whereNotNull('opened_at'),
                    'recipients as clicked_recipients' => fn ($q) => $q->where('status', 'sent')->whereNotNull('clicked_at'),
                ])
                ->latest()
                ->get()
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
        $segments = $organization->segments()->get();
        $totalContacts = $organization->contacts()->where('status', 'subscribed')->count();

        return view('campaigns.create', compact('identities', 'templates', 'contactLists', 'segments', 'totalContacts'));
    }

    /**
     * Store a newly created campaign and snapshot target recipients.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanEditCampaigns($user);
        $organization = $user->currentOrganization();

        if (!$organization) {
            return redirect()->back()->withErrors(['error' => 'No active organization found.']);
        }

        // Every referenced record must belong to the current organization (tenant isolation).
        $request->validate([
            'name' => 'required|string|max:255',
            'sending_identity_id' => [
                'required',
                Rule::exists('sending_identities', 'id')->where('organization_id', $organization->id),
            ],
            'template_id' => [
                'nullable',
                Rule::exists('templates', 'id')->where('organization_id', $organization->id),
            ],
            'contact_list_id' => [
                'nullable',
                Rule::exists('contact_lists', 'id')->where('organization_id', $organization->id),
            ],
            'segment_id' => [
                'nullable',
                Rule::exists('segments', 'id')->where('organization_id', $organization->id),
            ],
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        // Ensure the sending identity is verified
        $identity = $organization->sendingIdentities()
            ->where('id', $request->sending_identity_id)
            ->where('verification_status', 'verified')
            ->first();

        if (!$identity) {
            return redirect()->back()->withInput()
                ->withErrors(['sending_identity_id' => 'Selected sending identity is invalid or not verified.']);
        }

        // Determine target contacts. A list takes precedence over a segment if
        // both are somehow submitted; leaving both blank targets everyone.
        $segment = null;

        if ($request->filled('contact_list_id')) {
            $list = $organization->contactLists()->find($request->contact_list_id);
            $contacts = $list ? $list->contacts()->where('status', 'subscribed')->get() : collect();
        } elseif ($request->filled('segment_id')) {
            $segment = $organization->segments()->find($request->segment_id);
            $contacts = $segment
                ? $segment->matchingContacts($organization)->filter(fn ($c) => $c->status === 'subscribed')->values()
                : collect();
        } else {
            // Target all subscribed contacts in the organization
            $contacts = $organization->contacts()->where('status', 'subscribed')->get();
        }

        if ($contacts->isEmpty()) {
            return redirect()->back()->withInput()
                ->withErrors(['contact_list_id' => 'No subscribed contacts found for the selected audience target.']);
        }

        DB::transaction(function () use ($organization, $identity, $segment, $request, $contacts) {
            $campaign = $organization->campaigns()->create([
                'sending_identity_id' => $identity->id,
                'template_id' => $request->template_id,
                'segment_id' => $segment?->id,
                'name' => trim($request->name),
                'subject' => trim($request->subject),
                'body' => $request->body,
                'status' => 'draft',
            ]);

            // Snapshot the chosen audience. Dispatch can narrow it (tag filter) but never widen it.
            $now = now();
            $recipientData = [];
            foreach ($contacts as $contact) {
                $recipientData[] = [
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contact->id,
                    'status' => 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($recipientData, 500) as $chunk) {
                DB::table('campaign_recipients')->insert($chunk);
            }
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
     * Dispatch the campaign via the background queue.
     *
     * The audience is the snapshot chosen when the campaign was created. An optional
     * tag filter can only NARROW it. Contacts that unsubscribed since the snapshot
     * are skipped as well.
     */
    public function dispatch(Request $request, int $id): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanEditCampaigns($user);
        $organization = $user->currentOrganization();

        $campaign = $organization
            ? $organization->campaigns()->find($id)
            : null;

        if (!$campaign) {
            return redirect()->route('campaigns.index')->withErrors(['error' => 'Campaign not found.']);
        }

        if ($campaign->status !== 'draft') {
            return redirect()->back()->withErrors(['error' => 'Campaign has already been dispatched, scheduled, or cancelled.']);
        }

        $request->validate([
            'mode' => 'nullable|in:now,schedule',
            'tag_filter' => 'nullable|string|max:100',
        ]);

        $mode = $request->input('mode', 'now');
        $tagFilter = trim((string) $request->input('tag_filter', ''));

        if ($mode === 'schedule') {
            // All scheduled times are UTC. Full per-organization timezone support
            // is a larger feature left for later; the form makes this explicit.
            $request->validate([
                'scheduled_at' => 'required|date|after:now',
            ]);

            $campaign->update([
                'status' => 'scheduled',
                'scheduled_at' => $request->input('scheduled_at'),
                'tag_filter' => $tagFilter !== '' ? $tagFilter : null,
            ]);

            return redirect()->route('campaigns.show', $campaign->id)
                ->with('status', 'Campaign scheduled for '.$campaign->fresh()->scheduled_at->format('M d, Y H:i').' UTC.');
        }

        $result = app(CampaignDispatchService::class)->execute($campaign, $tagFilter);

        if (!$result['ok']) {
            return redirect()->back()->withErrors(['error' => $result['error']]);
        }

        return redirect()->route('campaigns.show', $campaign->id)
            ->with('status', 'Campaign dispatched! Background workers are processing deliveries.');
    }

    /**
     * Cancel a campaign that is scheduled but hasn't sent yet. Cancelling is
     * final - a cancelled campaign cannot be resumed, only deleted or recreated.
     */
    public function cancel(int $id): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanEditCampaigns($user);
        $organization = $user->currentOrganization();

        $campaign = $organization ? $organization->campaigns()->find($id) : null;

        if (!$campaign) {
            return redirect()->route('campaigns.index')->withErrors(['error' => 'Campaign not found.']);
        }

        $updated = Campaign::where('id', $campaign->id)
            ->where('status', 'scheduled')
            ->update(['status' => 'cancelled']);

        if ($updated === 0) {
            return redirect()->back()->withErrors(['error' => 'Only a scheduled campaign can be cancelled.']);
        }

        return redirect()->route('campaigns.show', $campaign->id)
            ->with('status', 'Scheduled campaign cancelled.');
    }

    /**
     * Delete a draft or completed campaign.
     */
    public function destroy(int $id): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManage($user);
        $organization = $user->currentOrganization();

        if ($organization) {
            $organization->campaigns()->where('id', $id)->delete();
        }

        return redirect()->route('campaigns.index')
            ->with('status', 'Campaign deleted successfully.');
    }
}
