<?php

namespace App\Providers;

use App\Contracts\EmailProviderInterface;
use App\Services\EmailProviders\LogEmailProvider;
use App\Services\EmailProviders\SmtpEmailProvider;
use Illuminate\Support\ServiceProvider;

class EmailDeliveryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(EmailProviderInterface::class, function () {
            $driver = strtolower((string) env('EMAIL_PROVIDER', 'log'));

            switch ($driver) {
                case 'smtp':
                    return new SmtpEmailProvider();
                default:
                    return new LogEmailProvider();
            }
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