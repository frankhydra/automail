<?php

namespace App\Http\Controllers;

use App\Contracts\EmailProviderInterface;
use App\Models\SendingIdentity;
use App\Models\User;
use App\Services\DnsVerificationService;
use App\Services\PlanLimitService;
use App\Support\EnsuresTeamPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SendingIdentityController extends Controller
{
    use EnsuresTeamPermission;

    public function __construct(private readonly PlanLimitService $planLimits)
    {
    }

    public function index(DnsVerificationService $dns): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        $identities = $organization
            ? $organization->sendingIdentities()->orderBy('created_at', 'desc')->get()
            : collect();

        // Local development only: a signed link that verifies without sending email / editing DNS.
        $devVerifyLinks = [];
        if (app()->environment('local')) {
            foreach ($identities as $identity) {
                if ($identity->verification_status !== 'verified') {
                    $devVerifyLinks[$identity->id] = URL::signedRoute('sending-identities.verify', [
                        'token' => $identity->verification_token,
                    ]);
                }
            }
        }

        $records = [];
        foreach ($identities as $identity) {
            if ($identity->type === 'custom_domain') {
                $records[$identity->id] = $dns->expectedRecords($identity);
            }
        }

        return view('sending-identities.index', compact('identities', 'records', 'devVerifyLinks'));
    }

    /**
     * Register a sending identity.
     *
     * Free-mail addresses (gmail.com, ...) become "personal" identities verified by a
     * confirmation email. Any other domain becomes a "custom_domain" identity verified
     * through DNS records.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManage($user);
        $organization = $user->currentOrganization();

        if (!$organization) {
            return redirect()->back()->withErrors(['error' => 'No active organization found.']);
        }

        $data = $request->validate([
            'from_name' => 'required|string|max:255',
            'from_email' => 'required|email|max:255',
            'reply_to' => 'nullable|email|max:255',
        ]);

        $fromEmail = strtolower(trim($data['from_email']));
        $domain = substr(strrchr($fromEmail, '@'), 1);

        $exists = $organization->sendingIdentities()->where('from_email', $fromEmail)->exists();

        if ($exists) {
            return redirect()->back()->withInput()
                ->withErrors(['from_email' => 'This sender email is already registered in your organization.']);
        }

        if (!$this->planLimits->canAddSendingIdentity($organization)) {
            $limit = $this->planLimits->limits($organization)['sending_identities'];

            return redirect()->back()->withInput()
                ->withErrors(['error' => "Your plan allows up to {$limit} sending identity/identities. Upgrade your plan to add more."]);
        }

        $isPersonal = in_array($domain, config('automail.personal_domains', []), true);

        $organization->sendingIdentities()->create([
            'from_name' => trim($data['from_name']),
            'from_email' => $fromEmail,
            'reply_to' => !empty($data['reply_to']) ? strtolower(trim($data['reply_to'])) : null,
            'type' => $isPersonal ? 'personal' : 'custom_domain',
            'verification_status' => 'pending',
            'verification_token' => Str::random(40),
            'domain' => $domain,
            'dkim_selector' => config('automail.dkim_selector'),
            'spf_status' => 'unverified',
            'dkim_status' => 'unverified',
            'dmarc_status' => 'unverified',
        ]);

        $message = $isPersonal
            ? 'Sender added. Click "Send verification email" and open the link we send to that address.'
            : 'Sender added. Publish the DNS records shown below, then click "Check DNS".';

        return redirect()->route('sending-identities.index')->with('status', $message);
    }

    /**
     * Email a signed confirmation link to a personal address to prove mailbox ownership.
     */
    public function sendVerification(int $id, EmailProviderInterface $provider): RedirectResponse
    {
        $identity = $this->findForCurrentOrganization($id);

        if (!$identity) {
            return redirect()->route('sending-identities.index')->withErrors(['error' => 'Sending identity not found.']);
        }

        if ($identity->type !== 'personal') {
            return redirect()->route('sending-identities.index')
                ->withErrors(['error' => 'Custom domains are verified through DNS records, not by email.']);
        }

        if ($identity->verification_status === 'verified') {
            return redirect()->route('sending-identities.index')->with('status', 'This address is already verified.');
        }

        $link = URL::signedRoute('sending-identities.verify', ['token' => $identity->verification_token]);

        $body = '<p>Hello,</p>'
            .'<p>Please confirm that you own <strong>'.e($identity->from_email).'</strong> '
            .'and want to use it as a sender in AutoMail.</p>'
            .'<p><a href="'.e($link).'">Confirm this sender address</a></p>'
            .'<p>If you did not request this, you can ignore this email.</p>';

        $result = $provider->send(
            (string) config('mail.from.address'),
            (string) config('mail.from.name'),
            $identity->from_email,
            'Confirm your AutoMail sender address',
            $body,
            null
        );

        if (empty($result['success'])) {
            return redirect()->route('sending-identities.index')
                ->withErrors(['error' => 'Could not send the verification email: '.($result['error'] ?? 'unknown error')]);
        }

        return redirect()->route('sending-identities.index')
            ->with('status', 'Verification email sent to '.$identity->from_email.'. Open the link inside it to finish.');
    }

    /**
     * Confirm ownership via the signed link (public route: the mailbox owner may not be logged in).
     * The route is protected by the "signed" middleware, and the token is a 40 character secret.
     */
    public function verify(string $token): RedirectResponse
    {
        $identity = SendingIdentity::where('verification_token', $token)->first();

        if (!$identity) {
            return redirect()->route('sending-identities.index')->withErrors(['error' => 'Invalid verification link.']);
        }

        // Custom domains must pass the DNS check. The only exception is local development.
        if ($identity->type === 'custom_domain' && !app()->environment('local')) {
            return redirect()->route('sending-identities.index')
                ->withErrors(['error' => 'Custom domains are verified through DNS records.']);
        }

        $identity->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        return redirect()->route('sending-identities.index')
            ->with('status', $identity->from_email.' is now verified.');
    }

    /**
     * Look up the live DNS records of a custom domain and update the identity.
     */
    public function checkDns(int $id, DnsVerificationService $dns): RedirectResponse
    {
        $identity = $this->findForCurrentOrganization($id);

        if (!$identity) {
            return redirect()->route('sending-identities.index')->withErrors(['error' => 'Sending identity not found.']);
        }

        if ($identity->type !== 'custom_domain') {
            return redirect()->route('sending-identities.index')
                ->withErrors(['error' => 'Only custom domains use DNS verification.']);
        }

        $results = $dns->check($identity);
        $allPassed = !in_array(false, $results, true);

        $identity->update([
            'spf_status' => $results['spf'] ? 'verified' : 'unverified',
            'dkim_status' => $results['dkim'] ? 'verified' : 'unverified',
            'dmarc_status' => $results['dmarc'] ? 'verified' : 'unverified',
            'verification_status' => $allPassed ? 'verified' : 'pending',
            'verified_at' => $allPassed ? ($identity->verified_at ?? now()) : null,
        ]);

        if ($allPassed) {
            return redirect()->route('sending-identities.index')
                ->with('status', 'All DNS records found. '.$identity->domain.' is verified.');
        }

        $labels = ['ownership' => 'ownership TXT', 'spf' => 'SPF', 'dkim' => 'DKIM', 'dmarc' => 'DMARC'];
        $missing = [];
        foreach ($results as $key => $passed) {
            if (!$passed) {
                $missing[] = $labels[$key];
            }
        }

        return redirect()->route('sending-identities.index')
            ->withErrors(['error' => 'Not detected yet: '.implode(', ', $missing).'. DNS changes can take a while to propagate; try again later.']);
    }

    public function destroy(int $id): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManage($user);
        $organization = $user->currentOrganization();

        if ($organization) {
            $organization->sendingIdentities()->where('id', $id)->delete();
        }

        return redirect()->route('sending-identities.index')->with('status', 'Sending identity deleted.');
    }

    protected function findForCurrentOrganization(int $id): ?SendingIdentity
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        return $organization ? $organization->sendingIdentities()->find($id) : null;
    }
}
