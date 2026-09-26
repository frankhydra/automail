<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class TemplateBuilderTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_creating_a_template_with_blocks_compiles_and_stores_them(): void
    {
        [$user, $org] = $this->makeTenant();

        $blocks = [
            ['type' => 'header', 'title' => 'Welcome', 'subtitle' => 'Glad to have you'],
            ['type' => 'button', 'label' => 'Get Started', 'url' => 'https://example.com', 'color' => '#4f46e5'],
        ];

        $this->actingAs($user)->post(route('templates.store'), [
            'name' => 'Welcome Email',
            'subject' => 'Welcome!',
            'blocks_json' => json_encode($blocks),
        ])->assertSessionHasNoErrors();

        $template = $org->templates()->first();

        $this->assertNotNull($template->blocks);
        $this->assertCount(2, $template->blocks);
        $this->assertStringContainsString('Welcome', $template->body);
        $this->assertStringContainsString('Get Started', $template->body);
    }

    public function test_an_invalid_block_type_is_silently_dropped_not_saved(): void
    {
        [$user, $org] = $this->makeTenant();

        $blocks = [
            ['type' => 'header', 'title' => 'Kept'],
            ['type' => 'script', 'title' => 'Not a real block type'],
        ];

        $this->actingAs($user)->post(route('templates.store'), [
            'name' => 'Test',
            'blocks_json' => json_encode($blocks),
        ]);

        $template = $org->templates()->first();

        $this->assertCount(1, $template->blocks);
        $this->assertSame('header', $template->blocks[0]['type']);
    }

    public function test_the_raw_html_editor_still_works_unchanged(): void
    {
        [$user, $org] = $this->makeTenant();

        $this->actingAs($user)->post(route('templates.store'), [
            'name' => 'Raw HTML template',
            'subject' => 'Hi',
            'body' => '<p>Hand-written HTML</p>',
        ])->assertSessionHasNoErrors();

        $template = $org->templates()->first();

        $this->assertNull($template->blocks);
        $this->assertSame('<p>Hand-written HTML</p>', $template->body);
    }

    public function test_updating_a_template_can_switch_from_raw_html_to_blocks(): void
    {
        [$user, $org] = $this->makeTenant();
        $template = $org->templates()->create([
            'name' => 'Old',
            'subject' => 'Old subject',
            'body' => '<p>Old raw HTML</p>',
        ]);

        $this->actingAs($user)->put(route('templates.update', $template->id), [
            'name' => 'Old',
            'subject' => 'Old subject',
            'blocks_json' => json_encode([['type' => 'text', 'content' => 'New builder content']]),
        ])->assertSessionHasNoErrors();

        $template->refresh();

        $this->assertNotNull($template->blocks);
        $this->assertStringContainsString('New builder content', $template->body);
        $this->assertStringNotContainsString('Old raw HTML', $template->body);
    }

    public function test_a_template_without_a_name_is_rejected_even_with_blocks(): void
    {
        [$user] = $this->makeTenant();

        $this->actingAs($user)->post(route('templates.store'), [
            'blocks_json' => json_encode([['type' => 'text', 'content' => 'x']]),
        ])->assertSessionHasErrors('name');
    }
}
