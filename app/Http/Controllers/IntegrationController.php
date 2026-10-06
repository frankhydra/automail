<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Integration;
use App\Models\Organization;
use App\Models\User;
use App\Support\EnsuresTeamPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * The Integrations page. Everyone in the organization can see it; only owners and
 * admins can change it, because it holds a secret URL and affects every email sent.
 */
class IntegrationController extends Controller
{
    use EnsuresTeamPermission;

    protected function organization(): Organization
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        abort_if($organization === null, 403, 'No active organization found for your account.');

        return $organization;
    }

    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $organization = $this->organization();

        $webhook = Integration::forOrganization($organization->id, 'webhook');
        $utm = Integration::forOrganization($organization->id, 'utm');

        return view('integrations.index', [
            'webhook' => $webhook,
            'utm' => $utm,
            'canManage' => in_array($user->currentRole(), ['owner', 'admin'], true),
            'newUrl' => session('integration_url'),
        ]);
    }

    /**
     * Create the intake URL, or replace it (the old URL stops working immediately).
     * The URL is shown once, because only its hash is stored.
     */
    public function generateWebhook(): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManage($user);
        $organization = $this->organization();

        $token = Str::random(48);

        $integration = Integration::firstOrNew(['organization_id' => $organization->id, 'type' => 'webhook']);
        $integration->enabled = true;
        $integration->secret_hash = hash('sha256', $token);
        $integration->settings = $integration->settings ?? ['default_tags' => ''];
        $integration->save();

        return redirect()->route('integrations.index')
            ->with('integration_url', route('webhooks.contacts', ['token' => $token]))
            ->with('status', 'Your contact intake URL is ready. Copy it now: for security it is only shown once.');
    }

    public function updateWebhook(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManage($user);
        $organization = $this->organization();

        $integration = Integration::forOrganization($organization->id, 'webhook');

        if (!$integration) {
            return back()->withErrors(['webhook' => 'Generate an intake URL first.']);
        }

        $data = $request->validate([
            'enabled' => 'nullable|boolean',
            'default_tags' => 'nullable|string|max:200',
        ]);

        $integration->enabled = (bool) ($data['enabled'] ?? false);
        $integration->settings = ['default_tags' => implode(', ', Contact::splitTags((string) ($data['default_tags'] ?? '')))];
        $integration->save();

        return redirect()->route('integrations.index')->with('status', 'Webhook settings saved.');
    }

    public function updateUtm(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManage($user);
        $organization = $this->organization();

        $safe = ['regex:/^[A-Za-z0-9_.\-]+$/', 'max:50'];

        $data = $request->validate([
            'enabled' => 'nullable|boolean',
            'source' => array_merge(['required'], $safe),
            'medium' => array_merge(['required'], $safe),
            'domains' => 'nullable|string|max:500',
        ], [
            'source.regex' => 'Use only letters, numbers, dots, dashes and underscores.',
            'medium.regex' => 'Use only letters, numbers, dots, dashes and underscores.',
        ]);

        $domains = collect(preg_split('/[\s,]+/', strtolower((string) ($data['domains'] ?? '')), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($d) => preg_replace('#^https?://#', '', $d))
            ->map(fn ($d) => rtrim((string) $d, '/'))
            ->filter(fn ($d) => preg_match('/^[a-z0-9][a-z0-9.\-]*\.[a-z]{2,}$/', $d))
            ->unique()->take(10)->values()->all();

        $integration = Integration::firstOrNew(['organization_id' => $organization->id, 'type' => 'utm']);
        $integration->enabled = (bool) ($data['enabled'] ?? false);
        $integration->settings = ['source' => $data['source'], 'medium' => $data['medium'], 'domains' => $domains];
        $integration->save();

        Cache::forget('integration:utm:'.$organization->id);

        return redirect()->route('integrations.index')->with('status', 'Google Analytics tagging saved.');
    }
}
