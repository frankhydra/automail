<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class ApiTokenManagementTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_an_owner_can_create_and_revoke_a_token(): void
    {
        [$owner] = $this->makeTenant();

        $this->actingAs($owner)
            ->post(route('api-tokens.store'), ['name' => 'My Token'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $owner->tokens()->count());
        $tokenId = $owner->tokens()->first()->id;

        $this->actingAs($owner)
            ->delete(route('api-tokens.destroy', $tokenId))
            ->assertRedirect(route('api-tokens.index'));

        $this->assertSame(0, $owner->fresh()->tokens()->count());
    }

    public function test_a_manager_cannot_create_a_token(): void
    {
        [, $org] = $this->makeTenant();
        $manager = $this->attachMember($org, 'manager');

        $this->actingAs($manager)
            ->post(route('api-tokens.store'), ['name' => 'Nope'])
            ->assertForbidden();
    }

    public function test_an_admin_can_manage_tokens(): void
    {
        [, $org] = $this->makeTenant();
        $admin = $this->attachMember($org, 'admin');

        $this->actingAs($admin)
            ->post(route('api-tokens.store'), ['name' => 'Admin Token'])
            ->assertSessionHasNoErrors();
    }
}
