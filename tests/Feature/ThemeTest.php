<?php

namespace Tests\Feature;

use App\Support\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_guests_cannot_open_or_change_themes(): void
    {
        $this->get(route('themes.index'))->assertRedirect(route('login'));
        $this->put(route('themes.update'), ['palette' => 'ocean'])->assertRedirect(route('login'));
    }

    public function test_themes_page_lists_every_configured_theme(): void
    {
        [$user] = $this->makeTenant();

        $response = $this->actingAs($user)->get(route('themes.index'));

        $response->assertOk();
        foreach (Theme::all() as $palette) {
            $response->assertSee($palette['name']);
        }
    }

    public function test_user_can_pick_a_theme_and_it_is_saved(): void
    {
        [$user] = $this->makeTenant();

        $this->actingAs($user)
            ->put(route('themes.update'), ['palette' => 'ocean'])
            ->assertRedirect(route('themes.index'));

        $this->assertSame('ocean', $user->fresh()->palette);
    }

    public function test_unknown_theme_is_rejected(): void
    {
        [$user] = $this->makeTenant();

        $this->actingAs($user)
            ->put(route('themes.update'), ['palette' => 'neon-<script>'])
            ->assertSessionHasErrors('palette');

        $this->assertNull($user->fresh()->palette);
    }

    public function test_saved_theme_is_applied_to_the_page_and_stays_personal(): void
    {
        [$user, $org] = $this->makeTenant();
        $teammate = $this->attachMember($org, 'editor');

        $this->actingAs($user)->put(route('themes.update'), ['palette' => 'forest']);

        $this->actingAs($user)->get(route('dashboard'))->assertSee('data-palette="forest"', false);
        // A different member of the same organization still sees the default.
        $this->actingAs($teammate)->get(route('dashboard'))->assertSee('data-palette="ember"', false);
    }

    public function test_a_removed_theme_falls_back_to_the_default(): void
    {
        [$user] = $this->makeTenant();
        $user->forceFill(['palette' => 'no-longer-exists'])->save();

        $this->assertSame('ember', Theme::current($user->fresh()));
    }
}
