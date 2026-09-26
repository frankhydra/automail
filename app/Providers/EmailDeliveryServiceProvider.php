<?php

namespace App\Providers;

use App\Contracts\EmailProviderInterface;
use App\Services\EmailProviders\BrevoEmailProvider;
use App\Services\EmailProviders\LogEmailProvider;
use App\Services\EmailProviders\ResendEmailProvider;
use App\Services\EmailProviders\SmtpEmailProvider;
use Illuminate\Support\ServiceProvider;

class EmailDeliveryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * EMAIL_PROVIDER selects which adapter the whole app sends through. Campaign
     * code never touches these classes directly - it only depends on
     * EmailProviderInterface, so switching providers here is the only change
     * needed to change how every email in AutoMail is delivered.
     *
     * "ses" is not its own adapter: Amazon SES exposes a standard SMTP
     * interface, so set EMAIL_PROVIDER=smtp and point MAIL_HOST/MAIL_USERNAME/
     * MAIL_PASSWORD at the SMTP credentials from your SES console.
     */
    public function register(): void
    {
        $this->app->bind(EmailProviderInterface::class, function () {
            $driver = strtolower((string) env('EMAIL_PROVIDER', 'log'));

            return match ($driver) {
                'smtp' => new SmtpEmailProvider(),
                'brevo' => new BrevoEmailProvider((string) config('services.brevo.key')),
                'resend' => new ResendEmailProvider((string) config('services.resend.key')),
                default => new LogEmailProvider(),
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
