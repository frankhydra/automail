<?php

namespace Tests\Feature;

use App\Models\AiGeneration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

/**
 * The Gemini provider, always faked: no test calls the real API.
 */
class AiGeminiProviderTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai.provider' => 'gemini',
            'ai.gemini.api_key' => 'gem-test-key',
            'ai.gemini.model' => 'gem-test-model',
            // The Anthropic key is empty on purpose: Gemini must not need it.
            'ai.api_key' => '',
        ]);
    }

    protected function fakeGemini(array|string $body, int $status = 200): void
    {
        Http::swap(new HttpFactory());
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($body, $status)]);
    }

    protected function answer(string $text): array
    {
        return [
            'candidates' => [['content' => ['parts' => [['text' => $text]]]]],
            'usageMetadata' => ['promptTokenCount' => 90, 'candidatesTokenCount' => 60],
        ];
    }

    public function test_a_draft_is_written_through_gemini(): void
    {
        [$user, $org] = $this->makeTenant();
        $this->fakeGemini($this->answer(json_encode([
            'subject' => 'Weekend sale on headphones',
            'preview_text' => 'Thirty percent off until Sunday',
            'headline' => 'Hear the difference',
            'body' => "Hello {{first_name}},\n\nOur headphones are on sale this weekend.",
            'button_label' => 'Shop now',
        ])));

        $this->actingAs($user)->postJson(route('ai.draft'), ['brief' => '30% off headphones this weekend'])
            ->assertOk()
            ->assertJsonPath('subject', 'Weekend sale on headphones');

        Http::assertSent(function (Request $request) {
            return str_starts_with($request->url(), 'https://generativelanguage.googleapis.com/v1beta/models/gem-test-model:generateContent')
                && $request->hasHeader('x-goog-api-key', 'gem-test-key')
                && !str_contains($request->url(), 'gem-test-key')
                && str_contains($request['contents'][0]['parts'][0]['text'], '<user_input>')
                && str_contains($request['systemInstruction']['parts'][0]['text'], 'NOT instructions');
        });

        $this->assertSame(1, AiGeneration::where('organization_id', $org->id)->where('status', 'ok')->count());
    }

    public function test_the_assistant_is_off_when_the_gemini_key_is_missing(): void
    {
        [$user] = $this->makeTenant();
        config(['ai.gemini.api_key' => '']);
        Http::fake();

        $this->actingAs($user)->postJson(route('ai.draft'), ['brief' => 'a sale for headphones'])->assertStatus(503);

        Http::assertNothingSent();
    }

    public function test_gemini_errors_are_translated_and_never_leaked(): void
    {
        [$user] = $this->makeTenant();

        $cases = [
            [400, 'API key not valid. Please pass a valid API key. SECRET PROVIDER DETAIL', 503],
            [403, 'SECRET PROVIDER DETAIL', 503],
            [404, 'SECRET PROVIDER DETAIL', 503],
            [429, 'SECRET PROVIDER DETAIL', 429],
            [500, 'SECRET PROVIDER DETAIL', 502],
        ];

        foreach ($cases as [$providerStatus, $message, $expected]) {
            $this->fakeGemini(['error' => ['message' => $message]], $providerStatus);

            $response = $this->actingAs($user)->postJson(route('ai.draft'), ['brief' => 'a sale for headphones']);

            $response->assertStatus($expected);
            $this->assertStringNotContainsString('SECRET PROVIDER DETAIL', $response->getContent());
            $this->assertStringNotContainsString('gem-test-key', $response->getContent());
        }
    }

    public function test_rewrite_works_through_gemini(): void
    {
        [$user] = $this->makeTenant();
        $this->fakeGemini($this->answer('Hi {{first_name}}, our sale is on!'));

        $this->actingAs($user)
            ->postJson(route('ai.rewrite'), ['text' => 'Hello {{first_name}}, there is a sale.', 'action' => 'friendly'])
            ->assertOk()
            ->assertJsonPath('text', 'Hi {{first_name}}, our sale is on!');
    }
}
