<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\SuppressionList;
use App\Models\User;
use App\Support\EnsuresTeamPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactController extends Controller
{
    use EnsuresTeamPermission;

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

    public function index(Request $request): View
    {
        $organization = $this->organization();

        $search = $request->input('search');

        // Fetch contacts for the organization, applying search if present
        $contacts = $organization->contacts()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('tags', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('contacts.index', compact('contacts', 'search'));
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
