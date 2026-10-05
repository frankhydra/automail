<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Organization;
use App\Models\SuppressionList;
use App\Models\User;
use App\Services\PlanLimitService;
use App\Support\EnsuresTeamPermission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    use EnsuresTeamPermission;

    protected const STATUSES = ['subscribed', 'unsubscribed', 'bounced'];

    /**
     * The current user's organization, or a 403 if they have none.
     */
    protected function organization(): Organization
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        abort_if($organization === null, 403, 'No active organization found for your account.');

        return $organization;
    }

    /**
     * Contacts of the organization, narrowed by the search box, tag chip and status
     * filter. Shared by the list page and the CSV export so both always agree.
     */
    protected function filteredQuery(Request $request, Organization $organization): Builder
    {
        $search = trim((string) $request->input('search'));
        $tag = trim((string) $request->input('tag'));
        $status = (string) $request->input('status');

        // Contact::query() (not $organization->contacts()) so this is a plain Eloquent
        // Builder, matching the return type. Scoping to the organization is explicit here.
        $query = Contact::query()
            ->where('organization_id', $organization->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('tags', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, self::STATUSES, true), fn ($q) => $q->where('status', $status));

        if ($tag !== '') {
            // Tags are one comma separated string, so SQL can only pre-select candidates;
            // the exact per-tag match ("vip" must not match "non-vip") is done by hasAnyTag().
            $ids = $organization->contacts()
                ->where('tags', 'like', '%'.addcslashes($tag, '%_\\').'%')
                ->get(['id', 'tags'])
                ->filter(fn ($contact) => $contact->hasAnyTag($tag))
                ->pluck('id');

            $query->whereIn('id', $ids);
        }

        return $query;
    }

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $organization = $this->organization();

        $contacts = $this->filteredQuery($request, $organization)
            ->withCount([
                'campaignRecipients as sent_count' => fn ($q) => $q->where('status', 'sent'),
                'campaignRecipients as opened_count' => fn ($q) => $q->where('status', 'sent')->whereNotNull('opened_at'),
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Tag chips: every distinct tag used in this organization.
        $tags = $organization->contacts()
            ->whereNotNull('tags')->where('tags', '!=', '')
            ->distinct()->pluck('tags')
            ->flatMap(fn ($value) => Contact::splitTags($value))
            ->unique()->sort()->values()->take(14);

        $totalContacts = $organization->contacts()->count();

        return view('contacts.index', [
            'contacts' => $contacts,
            'tags' => $tags,
            'totalContacts' => $totalContacts,
            'search' => $request->input('search'),
            'activeTag' => mb_strtolower(trim((string) $request->input('tag'))),
            'activeStatus' => (string) $request->input('status'),
            'canManage' => in_array($user->currentRole(), ['owner', 'admin', 'manager'], true),
        ]);
    }

    public function store(Request $request, PlanLimitService $planLimits): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManageContacts($user);
        $organization = $this->organization();

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('contacts', 'email')->where('organization_id', $organization->id),
            ],
            'tags' => 'nullable|string|max:255',
        ]);

        $email = strtolower(trim($validated['email']));

        if (!$planLimits->canAddContacts($organization)) {
            return back()->withInput()->withErrors(['email' => 'You have reached the contact limit of your plan. Upgrade to add more contacts.']);
        }

        // Someone who unsubscribed or bounced must not be silently re-added as subscribed.
        $suppressed = SuppressionList::where('organization_id', $organization->id)->where('email', $email)->first();

        if ($suppressed) {
            return back()->withInput()->withErrors(['email' => "This address is on your suppression list ({$suppressed->reason}), so it can't be added as a subscriber."]);
        }

        $organization->contacts()->create([
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'email' => $email,
            'status' => 'subscribed',
            'tags' => isset($validated['tags']) && trim($validated['tags']) !== '' ? trim($validated['tags']) : null,
        ]);

        return redirect()->route('contacts.index')->with('status', 'Contact added successfully!');
    }

    /**
     * Download the (filtered) audience as a CSV file.
     * Restricted to roles that can manage contacts, because it exports personal data.
     */
    public function export(Request $request): StreamedResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManageContacts($user);
        $organization = $this->organization();

        $query = $this->filteredQuery($request, $organization)->orderBy('id');

        $filename = 'audience-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['first_name', 'last_name', 'email', 'status', 'tags', 'added_at']);

            foreach ($query->cursor() as $contact) {
                fputcsv($out, array_map([$this, 'csvSafe'], [
                    $contact->first_name,
                    $contact->last_name,
                    $contact->email,
                    $contact->status,
                    $contact->tags,
                    $contact->created_at?->toDateString(),
                ]));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Stop spreadsheet apps from running a cell as a formula (CSV injection):
     * values starting with = + - @ are prefixed with an apostrophe.
     */
    protected function csvSafe(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    public function edit(int $id): View|RedirectResponse
    {
        $organization = $this->organization();

        $contact = $organization->contacts()->find($id);

        if (!$contact) {
            return redirect()->route('contacts.index')->withErrors(['error' => 'Contact not found.']);
        }

        return view('contacts.edit', compact('contact'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManageContacts($user);
        $organization = $this->organization();

        $contact = $organization->contacts()->find($id);

        if (!$contact) {
            return redirect()->route('contacts.index')->withErrors(['error' => 'Contact not found.']);
        }

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                // Unique per organization, ignoring this contact itself.
                Rule::unique('contacts', 'email')
                    ->where('organization_id', $organization->id)
                    ->ignore($contact->id),
            ],
            'status' => 'required|in:subscribed,unsubscribed,bounced',
            'tags' => 'nullable|string|max:255',
        ]);

        $validated['email'] = strtolower(trim($validated['email']));

        $wasSubscribed = $contact->status === 'subscribed';
        $contact->update($validated);

        // An admin who explicitly re-subscribes a contact lifts that address's suppression.
        if (!$wasSubscribed && $contact->status === 'subscribed') {
            SuppressionList::where('organization_id', $organization->id)
                ->where('email', $contact->email)
                ->delete();
        }

        return redirect()->route('contacts.index')->with('status', 'Contact updated successfully!');
    }

    public function destroy(int $id): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManage($user);
        $organization = $this->organization();

        $organization->contacts()->where('id', $id)->delete();

        return redirect()->route('contacts.index')->with('status', 'Contact deleted successfully.');
    }
}
