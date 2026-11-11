<?php

use App\Http\Middleware\EnsureSportScope;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Providers\FortifyServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        FortifyServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind Traefik on the server: trust its forwarded host, scheme and client IP.
        $middleware->trustProxies(at: '*');
        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: [
            SetLocale::class,
        ]);
        $middleware->alias([
            'sport.scope' => EnsureSportScope::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            '/mp/webhook',
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        $schedule->command('fees:generate')->monthlyOn(1, '06:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
