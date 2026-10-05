<?php

if (! enum_exists('SortDirection')) {
    enum SortDirection
    {
        case Ascending;
        case Descending;
    }
}

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        // Fires any campaign whose scheduled send time has arrived.
        // Locally: `php artisan schedule:work` keeps this running while you develop.
        // In production this needs a real cron entry running `php artisan schedule:run` every minute.
        $schedule->command('campaigns:process-scheduled')->everyMinute();

        // Moves contacts along their automation journeys (waits, conditions, emails).
        $schedule->command('automations:process')->everyMinute()->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        // Providers calling our webhooks don't send Laravel's CSRF token - each
        // webhook verifies itself instead (a shared secret or an HMAC signature;
        // see WebhookController).
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();