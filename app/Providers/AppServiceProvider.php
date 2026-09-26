<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Send the "verify your email" notification automatically whenever a new
        // user registers. Declared explicitly here rather than relying on
        // Laravel's default event-discovery, so this behavior is easy to find
        // and won't silently change between framework versions.
        Event::listen(Registered::class, SendEmailVerificationNotification::class);
    }
}
