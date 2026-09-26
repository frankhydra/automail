<?php

namespace Tests\Feature;

use App\Services\EmailProviders\BrevoEmailProvider;
use App\Services\EmailProviders\ResendEmailProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmailProviderAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_brevo_provider_sends_the_expected_request_and_reports_success(): void
    {
        Http::fake([
            'api.brevo.com/*' => Http::response(['messageId' => 'brevo-123'], 201),
        ]);

        $provider = new BrevoEmailProvider('fake-api-key');

        $result = $provider->send('sender@example.com', 'Sender', 'to@example.com', 'Hi', '<p>Body</p>', 'reply@example.com');

        $this->assertTrue($result['success']);
        $this->assertSame('brevo-123', $result['message_id']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.brevo.com/v3/smtp/email'
                && $request->hasHeader('api-key', 'fake-api-key')
                && $request['sender']['email'] === 'sender@example.com'
                && $request['to'][0]['email'] === 'to@example.com'
                && $request['htmlContent'] === '<p>Body</p>'
                && $request['replyTo']['email'] === 'reply@example.com';
        });
    }

    public function test_brevo_provider_reports_failure_on_a_rejected_request(): void
    {
        Http::fake([
            'api.brevo.com/*' => Http::response(['message' => 'invalid sender'], 400),
        ]);

        $result = (new BrevoEmailProvider('fake-api-key'))
            ->send('sender@example.com', 'Sender', 'to@example.com', 'Hi', '<p>Body</p>');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('invalid sender', $result['error']);
    }

    public function test_resend_provider_sends_the_expected_request_and_reports_success(): void
    {
        Http::fake([
            'api.resend.com/*' => Http::response(['id' => 'resend-abc'], 200),
        ]);

        $provider = new ResendEmailProvider('fake-api-key');

        $result = $provider->send('sender@example.com', 'Sender', 'to@example.com', 'Hi', '<p>Body</p>');

        $this->assertTrue($result['success']);
        $this->assertSame('resend-abc', $result['message_id']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.resend.com/emails'
                && $request->hasHeader('Authorization', 'Bearer fake-api-key')
                && $request['from'] === 'Sender <sender@example.com>'
                && $request['to'][0] === 'to@example.com';
        });
    }

    public function test_resend_provider_reports_failure_on_a_rejected_request(): void
    {
        Http::fake([
            'api.resend.com/*' => Http::response(['message' => 'domain not verified'], 422),
        ]);

        $result = (new ResendEmailProvider('fake-api-key'))
            ->send('sender@example.com', 'Sender', 'to@example.com', 'Hi', '<p>Body</p>');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('domain not verified', $result['error']);
    }

    public function test_providers_throw_a_clear_error_when_their_api_key_is_missing(): void
    {
        $this->expectExceptionMessage('BREVO_API_KEY is not set');
        new BrevoEmailProvider('');
    }
}
