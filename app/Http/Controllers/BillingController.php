<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PlanLimitService;
use App\Support\EnsuresTeamPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BillingController extends Controller
{
    use EnsuresTeamPermission;

    public function index(PlanLimitService $plans): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManage($user);
        $organization = $user->currentOrganization();

        return view('billing.index', [
            'organization' => $organization,
            'currentPlanKey' => $organization->plan,
            'plans' => config('plans'),
            'usage' => $plans->usageSummary($organization),
        ]);
    }

    /**
     * Switches the organization's plan immediately, with no payment step.
     * In a production version this would redirect to a payment provider
     * (e.g. Stripe Checkout) and only apply the plan once payment succeeds -
     * see the milestone notes for why that isn't built here.
     */
    public function upgrade(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManage($user);
        $organization = $user->currentOrganization();

        $data = $request->validate([
            'plan' => ['required', Rule::in(array_keys(config('plans')))],
        ]);

        $organization->update(['plan' => $data['plan']]);

        $planName = config('plans.'.$data['plan'].'.name');

        return redirect()->route('billing.index')
            ->with('status', "Switched to the {$planName} plan. (No payment was charged - this demo doesn't process real payments yet.)");
    }
}
