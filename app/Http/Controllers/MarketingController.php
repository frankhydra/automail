<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * The public website: home, features, pricing and the "solutions" pages.
 * All wording comes from config/marketing.php; plan numbers come from
 * config/plans.php and config/ai.php, so they always match what the app enforces.
 */
class MarketingController extends Controller
{
    public function home(): View
    {
        return view('marketing.home', $this->shared());
    }

    public function features(): View
    {
        return view('marketing.features', $this->shared());
    }

    public function pricing(): View
    {
        return view('marketing.pricing', $this->shared() + ['plans' => $this->plans()]);
    }

    public function audience(string $slug): View
    {
        $audience = config("marketing.audiences.{$slug}");

        abort_if($audience === null, 404);

        // Code samples show this site's real address instead of a placeholder host.
        $base = rtrim(url('/'), '/');
        foreach ($audience['code'] ?? [] as $i => $sample) {
            $audience['code'][$i]['code'] = str_replace('{APP_URL}', $base, $sample['code']);
        }

        return view('marketing.audience', $this->shared() + ['audience' => $audience, 'slug' => $slug]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function shared(): array
    {
        return [
            'capabilities' => config('marketing.capabilities'),
            'menuCapabilities' => config('marketing.menu_capabilities'),
            'audiences' => config('marketing.audiences'),
            'themeCount' => count((array) config('themes.palettes', [])),
        ];
    }

    /**
     * The plans for display: name, price split into "$19" and "/mo", and readable limits.
     *
     * @return list<array<string, mixed>>
     */
    protected function plans(): array
    {
        $aiLimits = (array) config('ai.limits');
        $plans = [];

        foreach ((array) config('plans') as $key => $plan) {
            [$amount, $period] = array_pad(explode('/', (string) $plan['price_display'], 2), 2, 'mo');
            $limits = (array) $plan['limits'];

            $plans[] = [
                'key' => $key,
                'name' => $plan['name'],
                'amount' => $amount,
                'period' => '/'.$period,
                'contacts' => $limits['contacts'] ?? null,
                'emails' => $limits['monthly_emails'] ?? null,
                'team' => $limits['team_members'] ?? null,
                'identities' => $limits['sending_identities'] ?? null,
                'ai' => $aiLimits[$key] ?? null,
                'featured' => $key === 'business',
                'blurb' => match ($key) {
                    'free' => 'Try everything with a small list.',
                    'starter' => 'For a growing newsletter or shop.',
                    'business' => 'Best for growing teams.',
                    'pro' => 'No limits on volume.',
                    default => '',
                },
            ];
        }

        return $plans;
    }
}
