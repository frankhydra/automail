<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class MarketingSiteTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    /** Every public page a visitor can reach. */
    protected function publicPaths(): array
    {
        $paths = ['/', '/features', '/pricing'];

        foreach (array_keys(config('marketing.audiences')) as $slug) {
            $paths[] = "/for/{$slug}";
        }

        return $paths;
    }

    public function test_every_public_page_loads_for_a_guest(): void
    {
        foreach ($this->publicPaths() as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_an_unknown_solution_page_is_a_404(): void
    {
        $this->get('/for/aliens')->assertNotFound();
        $this->get('/for/Marketers')->assertNotFound(); // slugs are lowercase only
    }

    public function test_every_page_has_a_unique_title_and_a_description(): void
    {
        $titles = [];

        foreach ($this->publicPaths() as $path) {
            $html = $this->get($path)->getContent();

            preg_match('#<title>(.*?)</title>#s', $html, $m);
            $titles[] = trim(html_entity_decode($m[1] ?? ''));

            $this->assertMatchesRegularExpression('#<meta name="description" content="[^"]{20,}"#', $html, "{$path} needs a meta description");
            $this->assertSame(1, substr_count($html, '<h1'), "{$path} should have exactly one h1");
        }

        $this->assertSame(count($titles), count(array_unique($titles)), 'page titles must be unique');
        $this->assertNotContains('', $titles);
    }

    public function test_no_internal_link_on_the_site_is_dead(): void
    {
        $base = rtrim(url('/'), '/');
        $checked = [];

        foreach ($this->publicPaths() as $path) {
            $html = $this->get($path)->getContent();
            preg_match_all('#href="([^"]+)"#', $html, $m);

            foreach (array_unique($m[1]) as $href) {
                $href = html_entity_decode($href);

                if (str_starts_with($href, $base)) {
                    $href = substr($href, strlen($base)) ?: '/';
                }

                // external links, anchors on the same page, mail and asset URLs are not our concern
                if (!str_starts_with($href, '/') || str_starts_with($href, '//') || str_starts_with($href, '/build/')) {
                    continue;
                }

                $target = explode('#', $href)[0] ?: '/';

                if (isset($checked[$target])) {
                    continue;
                }

                $status = $this->get($target)->getStatusCode();
                $checked[$target] = $status;

                $this->assertContains($status, [200, 302], "Dead link {$href} on {$path} answered {$status}");
            }
        }

        $this->assertNotEmpty($checked);
    }

    public function test_guests_see_login_and_signup_and_members_see_the_dashboard(): void
    {
        $this->get('/')->assertSee('Log in')->assertSee('Start free')->assertDontSee('Open dashboard');

        [$user] = $this->makeTenant();
        $this->actingAs($user)->get('/')->assertSee('Open dashboard')->assertDontSee('Log in');
    }

    public function test_the_pricing_page_always_matches_the_plans_the_app_enforces(): void
    {
        config(['plans.starter.price_display' => '$23/mo', 'plans.starter.limits.contacts' => 4321]);

        $this->get('/pricing')->assertOk()->assertSee('$23')->assertSee('4,321');

        foreach (config('plans') as $plan) {
            $this->get('/pricing')->assertSee($plan['name']);
        }
    }

    public function test_unlimited_plans_say_so_and_ai_allowances_come_from_config(): void
    {
        config(['ai.limits.business' => 777]);

        $this->get('/pricing')->assertSee('Unlimited')->assertSee('777');
    }

    public function test_the_pricing_page_does_not_promise_payment_features_that_do_not_exist(): void
    {
        $html = strtolower($this->get('/pricing')->getContent().$this->get('/')->getContent());

        foreach (['credit card required', 'cancel anytime', 'money-back', 'free trial', 'soc 2', 'gdpr compliant'] as $claim) {
            $this->assertStringNotContainsString($claim, $html, "Unsupported claim on the site: {$claim}");
        }
    }

    public function test_the_developer_page_shows_real_addresses_and_no_placeholders(): void
    {
        $html = $this->get('/for/developers')->assertOk()->getContent();

        $this->assertStringNotContainsString('{APP_URL}', $html);
        $this->assertStringContainsString(url('/').'/webhooks/contacts/YOUR_SECRET_TOKEN', html_entity_decode($html));
        $this->assertStringContainsString('/api/v1/contacts', $html);
    }

    public function test_merge_tag_examples_show_literally_and_are_not_swallowed_by_blade(): void
    {
        $this->get('/')->assertSee('{{first_name}}', false);
    }

    public function test_the_navigation_lists_every_solution_and_feature(): void
    {
        $html = $this->get('/')->getContent();

        foreach (config('marketing.audiences') as $slug => $aud) {
            $this->assertStringContainsString(route('marketing.audience', $slug), $html);
        }

        foreach (config('marketing.menu_capabilities') as $key) {
            $this->assertStringContainsString(route('marketing.features').'#'.$key, $html);
        }
    }

    public function test_the_app_itself_is_still_protected(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}
