<?php

require_once __DIR__.'/../app/Support/helpers.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
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

            // Validation API uses `{reading}` and expects authorization failures (403) rather than hidden 404s.
            // Bind without tenant scope so policies can decide access.
            Route::bind('reading', function (string $value) {
                $reading = \App\Models\MeterReading::withoutGlobalScopes()->with('meter')->find($value);

                if (!$reading) {
                    abort(404, 'Reading not found.');
                }

                return $reading;
            });

            // Bind service configurations without tenant scope for validation endpoints.
            Route::bind('serviceConfiguration', function (string $value) {
                return \App\Models\ServiceConfiguration::withoutGlobalScopes()->findOrFail($value);
            });

            // Same pattern for estimated readings in v1 routes.
            Route::bind('estimatedReading', function (string $value) {
                return \App\Models\MeterReading::withoutGlobalScopes()->with('meter')->findOrFail($value);
            });
            
            // Explicit binding for organization parameter to ensure consistent tenant resolution
            Route::bind('organization', function (string $value) {
                return \App\Models\Organization::query()->findOrFail($value);
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // GEMINI FIX: Check CLI arguments directly to detect testing framework
        // This bypasses the Laravel bootstrapping timing issue completely.
        $isTesting = false;
        if (php_sapi_name() === 'cli') {
            foreach ($_SERVER['argv'] ?? [] as $arg) {
                if (str_contains($arg, 'phpunit') || str_contains($arg, 'pest')) {
                    $isTesting = true;
                    break;
                }
            }
        }

        if ($isTesting) {
            $middleware->validateCsrfTokens(except: ['*']);
        }

        // Register middleware aliases
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'tenant.context' => \App\Http\Middleware\EnsureTenantContext::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'subscription.check' => \App\Http\Middleware\CheckSubscriptionStatus::class,
            'hierarchical.access' => \App\Http\Middleware\EnsureHierarchicalAccess::class,
            'locale' => \App\Http\Middleware\SetLocale::class,
            'impersonation' => \App\Http\Middleware\HandleImpersonation::class,
            'superadmin.rate_limit' => \App\Http\Middleware\RateLimitSuperadminOperations::class,
        ]);

        // Apply global middleware
        $middleware->appendToGroup('web', \App\Http\Middleware\SetLocale::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\HandleImpersonation::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\SecurityHeaders::class);

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
