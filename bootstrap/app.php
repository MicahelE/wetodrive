<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        // Pull fresh Polar state before expiring, so a renewed-but-unsynced
        // subscription isn't wrongly expired (renewals arrive only via webhook).
        $schedule->command('polar:reconcile')->dailyAt('08:55');
        $schedule->command('subscriptions:notify-expiring --days=3')->dailyAt('09:00');
        $schedule->command('subscriptions:expire')->dailyAt('09:05');
        $schedule->command('emails:send-check-ins')->dailyAt('10:00');

        // Features announcement drip: paused 2026-08-27, backlog done (521 sent,
        // 0 eligible left). Left off because feature_email_sent defaults to false,
        // so new signups would otherwise get an announcement about a feature that
        // was already there when they joined. Re-enable only for a new announcement.
        // $schedule->command('users:announce-features')->dailyAt('11:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        // First-touch attribution. Has to run on the landing request, because the
        // referrer is gone by the time anyone reaches the sign-in.
        $middleware->web(append: [
            \App\Http\Middleware\CaptureSignupSource::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);

        // App authenticates via Google OAuth only (no 'login' route), so send
        // unauthenticated visitors to the home page where the sign-in lives.
        $middleware->redirectGuestsTo(fn () => route('home'));

        $middleware->validateCsrfTokens(except: [
            'webhooks/polar',
            'webhooks/paystack',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();