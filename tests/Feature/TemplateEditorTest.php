<?php

namespace Tests\Feature;

use App\Contracts\EmailProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenants;
use Tests\Support\FakeEmailProvider;
use Tests\TestCase;

class TemplateEditorTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_preview_text_is_saved_and_compiled_into_the_body(): void
    {
        [$user, $org] = $this->makeTenant();

        $this->actingAs($user)->post(route('templates.store'), [
            'name' => 'Newsletter',
            'subject' => 'Hi',
            'preview_text' => 'Open for early access',
            'blocks_json' => json_encode([['type' => 'text', 'content' => 'Hello']]),
        ])->assertSessionHasNoErrors();

        $template = $org->templates()->first();

        $this->assertSame('Open for early access', $template->preview_text);
        $this->assertStringContainsString('Open for early access', $template->body);
    }

    public function test_the_editor_pages_load(): void
    {
        [$user, $org] = $this->makeTenant();
        $template = $org->templates()->create(['name' => 'T', 'body' => '<p>x</p>']);

        $this->actingAs($user)->get(route('templates.create'))->assertOk();
        $this->actingAs($user)->get(route('templates.edit', $template->id))->assertOk();
        $this->actingAs($user)->get(route('templates.index'))->assertOk();
    }

    public function test_preview_returns_rendered_html_with_sample_data(): void
    {
        [$user] = $this->makeTenant();

        $this->actingAs($user)->postJson(route('templates.preview'), [
            'subject' => 'Hi {{first_name}}',
            'blocks_json' => json_encode([['type' => 'text', 'content' => 'Dear {{first_name}}']]),
        ])->assertOk()->assertJsonPath('subject', 'Hi John')->assertJsonStructure(['subject', 'html']);
    }

    public function test_send_test_goes_to_a_team_member_through_the_provider(): void
    {
        [$user, $org] = $this->makeTenant();
        $this->makeIdentity($org);

        $fake = new FakeEmailProvider();
        $this->app->instance(EmailProviderInterface::class, $fake);

        $this->actingAs($user)->postJson(route('templates.send-test'), [
            'email' => $user->email,
            'subject' => 'Welcome',
            'blocks_json' => json_encode([['type' => 'text', 'content' => 'Hello']]),
        ])->assertOk();

        $this->assertCount(1, $fake->sent);
        $this->assertSame($user->email, $fake->sent[0]['toEmail']);
        $this->assertSame('[Test] Welcome', $fake->sent[0]['subject']);
    }

    public function test_send_test_refuses_addresses_outside_the_team(): void
    {
        [$user, $org] = $this->makeTenant();
        $this->makeIdentity($org);

        $fake = new FakeEmailProvider();
        $this->app->instance(EmailProviderInterface::class, $fake);

        $this->actingAs($user)->postJson(route('templates.send-test'), [
            'email' => 'stranger@example.com',
            'blocks_json' => json_encode([['type' => 'text', 'content' => 'Hello']]),
        ])->assertStatus(422);

        $this->assertCount(0, $fake->sent);
    }

    public function test_send_test_needs_a_verified_sending_identity(): void
    {
        [$user, $org] = $this->makeTenant();
        $this->makeIdentity($org, false);

        $this->app->instance(EmailProviderInterface::class, new FakeEmailProvider());

        $this->actingAs($user)->postJson(route('templates.send-test'), [
            'email' => $user->email,
            'blocks_json' => json_encode([['type' => 'text', 'content' => 'Hello']]),
        ])->assertStatus(422);
    }

    public function test_a_viewer_cannot_send_a_test(): void
    {
        [$owner, $org] = $this->makeTenant();
        $this->makeIdentity($org);
        $viewer = $this->attachMember($org, 'viewer');

        $this->app->instance(EmailProviderInterface::class, new FakeEmailProvider());

        $this->actingAs($viewer)->postJson(route('templates.send-test'), [
            'email' => $viewer->email,
            'blocks_json' => '[]',
        ])->assertForbidden();
    }
}
