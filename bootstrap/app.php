<?php

use App\Http\Middleware\EnsureAccountIsAccessible;
use App\Http\Middleware\EnsureOnboardingIsComplete;
use App\Http\Middleware\EnsureUserIsTenant;
use App\Http\Middleware\SetAuthenticatedUserLocale;
use App\Http\Middleware\SetGuestLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetGuestLocale::class,
        ]);

        $middleware->alias([
            'set.auth.locale' => SetAuthenticatedUserLocale::class,
            'ensure.account.accessible' => EnsureAccountIsAccessible::class,
            'ensure.onboarding.complete' => EnsureOnboardingIsComplete::class,
            'tenant.only' => EnsureUserIsTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
