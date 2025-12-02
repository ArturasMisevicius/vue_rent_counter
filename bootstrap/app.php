<?php

require_once __DIR__.'/../app/Support/helpers.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Performance optimization: Eager load relationships for route model binding
            // Reduces N+1 queries in controllers and form requests
            Route::bind('meterReading', function (string $value) {
                return \App\Models\MeterReading::with('meter')->findOrFail($value);
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register middleware aliases for route-level application
        // These can be applied via ->middleware() in route definitions
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'tenant.context' => \App\Http\Middleware\EnsureTenantContext::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'subscription.check' => \App\Http\Middleware\CheckSubscriptionStatus::class,
            'hierarchical.access' => \App\Http\Middleware\EnsureHierarchicalAccess::class,
            'locale' => \App\Http\Middleware\SetLocale::class,
            'impersonation' => \App\Http\Middleware\HandleImpersonation::class,
        ]);

        // Disable CSRF protection during unit tests for easier testing
        if (app()->runningUnitTests()) {
            $middleware->remove(ValidateCsrfToken::class);
            $middleware->removeFromGroup('web', ValidateCsrfToken::class);
        }

        // Apply middleware to all web routes
        // - SetLocale: Handles i18n based on session/user preference
        // - HandleImpersonation: Manages superadmin impersonation sessions
        // - SecurityHeaders: Applies CSP, X-Frame-Options, HSTS headers
        $middleware->appendToGroup('web', \App\Http\Middleware\SetLocale::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\HandleImpersonation::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\SecurityHeaders::class);
        
        // API rate limiting: 60 requests per minute per IP
        // Note: Admin/Filament routes rely on Filament's built-in protections
        // and SecurityHeaders middleware for DoS prevention
        $middleware->throttleApi('60,1');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Custom exception handling for authorization failures
        // Logs failed authorization attempts and returns user-friendly responses
        // Requirement 9.4: Security audit logging for access control violations
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
