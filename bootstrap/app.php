<?php

use App\Console\Commands\SyncTranslationsCommand;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\BlockBlockedIpAddresses;
use App\Http\Middleware\CheckManagerPermission;
use App\Http\Middleware\EnsureAccountIsAccessible;
use App\Http\Middleware\EnsureOnboardingIsComplete;
use App\Http\Middleware\EnsureUserIsTenant;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetAuthenticatedUserLocale;
use App\Http\Middleware\SetGuestLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withCommands([
        SyncTranslationsCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            BlockBlockedIpAddresses::class,
        ]);

        $middleware->web(append: [
            SetGuestLocale::class,
            SecurityHeaders::class,
        ]);

        $middleware->alias([
            'auth' => Authenticate::class,
            'set.auth.locale' => SetAuthenticatedUserLocale::class,
            'ensure.account.accessible' => EnsureAccountIsAccessible::class,
            'ensure.onboarding.complete' => EnsureOnboardingIsComplete::class,
            'manager.permission' => CheckManagerPermission::class,
            'tenant.only' => EnsureUserIsTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create()
    ->usePublicPath(dirname(__DIR__));
