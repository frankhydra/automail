<?php

namespace App\Http\Controllers;

use App\Models\SendingIdentity;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SendingIdentityController extends Controller
{
    /**
     * Display a listing of sending identities for the current organization.
     */
    public function index(): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        $identities = $organization
            ? $organization->sendingIdentities()->latest()->get()
            : collect();

        return view('sending-identities.index', compact('identities'));
    }

    /**
     * Store a new personal sending identity.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'from_name' => 'required|string|max:255',
            'from_email' => 'required|email|max:255',
            'reply_to' => 'nullable|email|max:255',
        ]);

        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        if (!$organization) {
            return redirect()->back()->withErrors(['error' => 'No active organization found.']);
        }

        // Check for duplicate sender email in this organization
        $existing = $organization->sendingIdentities()
            ->where('from_email', strtolower($request->from_email))
            ->first();

        if ($existing) {
            return redirect()->back()->withErrors(['from_email' => 'This sender email is already registered in your organization.']);
        }

        $token = Str::random(40);

        $organization->sendingIdentities()->create([
            'from_name' => trim($request->from_name),
            'from_email' => strtolower(trim($request->from_email)),
            'reply_to' => $request->reply_to ? strtolower(trim($request->reply_to)) : null,
            'type' => 'personal',
            'verification_status' => 'pending',
            'verification_token' => $token,
        ]);

        return redirect()->route('sending-identities.index')
            ->with('status', 'Sending identity added! Click "Verify Now" to simulate address confirmation.');
    }

    /**
     * Simulate address verification via token link.
     */
    public function verify(string $token): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        if (!$organization) {
            return redirect()->route('sending-identities.index')->withErrors(['error' => 'No active organization found.']);
        }

        $identity = $organization->sendingIdentities()
            ->where('verification_token', $token)
            ->first();

        if (!$identity) {
            return redirect()->route('sending-identities.index')
                ->withErrors(['error' => 'Invalid or expired verification token.']);
        }

        $identity->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
            'verification_token' => null,
        ]);

        return redirect()->route('sending-identities.index')
            ->with('status', "Sending identity {$identity->from_email} has been successfully verified!");
    }

    /**
     * Remove a sending identity.
     */
    public function destroy(int $id): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        if ($organization) {
            $organization->sendingIdentities()->where('id', $id)->delete();
        }

        return redirect()->route('sending-identities.index')
            ->with('status', 'Sending identity deleted.');
    }
}