<?php

namespace App\Http\Controllers;

use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

/**
 * Public routes (signed, not behind auth) that a person opens from the email
 * TeamController sends. Three outcomes, all handled here:
 *  - already logged in as the invited email -> attach immediately
 *  - not logged in but an account with that email exists -> send to login
 *  - no account exists yet -> a short signup form, right on this page
 */
class InvitationController extends Controller
{
    public function accept(string $token): View|RedirectResponse
    {
        $invitation = OrganizationInvitation::where('token', $token)->pending()->first();

        if (!$invitation || $invitation->isExpired()) {
            return redirect()->route('login')->withErrors(['error' => 'This invitation is invalid or has expired.']);
        }

        $existingUser = User::where('email', $invitation->email)->first();

        /** @var User|null $authUser */
        $authUser = Auth::user();

        if ($authUser) {
            if (strtolower($authUser->email) !== strtolower($invitation->email)) {
                return view('invitations.accept', [
                    'invitation' => $invitation,
                    'mode' => 'wrong_account',
                ]);
            }

            return $this->attach($invitation, $authUser);
        }

        if ($existingUser) {
            // So that after logging in, Laravel's normal redirect()->intended()
            // sends them straight back here to finish joining, instead of the
            // default dashboard redirect.
            session(['url.intended' => request()->fullUrl()]);

            return view('invitations.accept', [
                'invitation' => $invitation,
                'mode' => 'existing_account',
            ]);
        }

        return view('invitations.accept', [
            'invitation' => $invitation,
            'mode' => 'new_account',
        ]);
    }

    /**
     * Handles the on-page signup form for someone with no AutoMail account yet.
     * Creates the user WITHOUT the default organization normal registration
     * creates - they join the inviting organization instead.
     */
    public function register(Request $request, string $token): RedirectResponse
    {
        $invitation = OrganizationInvitation::where('token', $token)->pending()->first();

        if (!$invitation || $invitation->isExpired()) {
            return redirect()->route('login')->withErrors(['error' => 'This invitation is invalid or has expired.']);
        }

        if (User::where('email', $invitation->email)->exists()) {
            return redirect()->route('login')->withErrors(['error' => 'An account with this email already exists - please log in instead.']);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $invitation->email,
            'password' => Hash::make($data['password']),
        ]);

        event(new Registered($user));

        return $this->attach($invitation, $user);
    }

    protected function attach(OrganizationInvitation $invitation, User $user): RedirectResponse
    {
        $invitation->organization->users()->attach($user->id, ['role' => $invitation->role]);
        // Direct property assignment (not update()): accepted_at is deliberately
        // NOT in $fillable, since it must never be settable from request input -
        // this is the correct way for application code to still set it.
        $invitation->accepted_at = now();
        $invitation->save();

        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('status', "You've joined {$invitation->organization->name} as ".ucfirst($invitation->role).'.');
    }
}
