<?php

require_once __DIR__.'/../app/Support/helpers.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'tenant.context' => \App\Http\Middleware\EnsureTenantContext::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'subscription.check' => \App\Http\Middleware\CheckSubscriptionStatus::class,
            'hierarchical.access' => \App\Http\Middleware\EnsureHierarchicalAccess::class,
            'locale' => \App\Http\Middleware\SetLocale::class,
        ]);

        if (app()->runningUnitTests()) {
            $middleware->remove(ValidateCsrfToken::class);
            $middleware->removeFromGroup('web', ValidateCsrfToken::class);
        }

        $middleware->appendToGroup('web', \App\Http\Middleware\SetLocale::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle authorization exceptions with user-friendly messages (Requirement 9.4)
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, \Illuminate\Http\Request $request) {
            if (app()->runningUnitTests()) {
                throw $e;
            }

            // Log the authorization failure
            \Illuminate\Support\Facades\Log::warning('Authorization exception caught', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'user_role' => auth()->user()?->role?->value,
                'url' => $request->fullUrl(),
                'message' => $e->getMessage(),
                'timestamp' => now()->toDateTimeString(),
            ]);

            // Return user-friendly error response
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You do not have permission to perform this action.',
                    'error' => $e->getMessage() ?: 'Access denied',
                ], 403);
            }

            // For web requests, show the 403 error page
            return response()->view('errors.403', [
                'exception' => $e,
            ], 403);
        });
    })->create();
