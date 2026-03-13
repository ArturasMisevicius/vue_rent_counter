<?php

require_once __DIR__.'/../app/Support/helpers.php';

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\EnsureHierarchicalAccess;
use App\Http\Middleware\EnsureTenantContext;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsSuperadmin;
use App\Http\Middleware\HandleImpersonation;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetTenantContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

// Create the application with proper bootstrap sequence
$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            SetLocale::class,
            HandleImpersonation::class,
        ]);

        $middleware->alias([
            // Tenant context middleware
            'tenant.context' => EnsureTenantContext::class,
            'tenant.set' => SetTenantContext::class,

            // Role-based access control
            'superadmin' => EnsureUserIsSuperadmin::class,
            'role' => EnsureUserHasRole::class,

            // Subscription and hierarchical access (CRITICAL for admin/manager/tenant routes)
            'subscription.check' => CheckSubscriptionStatus::class,
            'hierarchical.access' => EnsureHierarchicalAccess::class,

            // Spatie Laravel Permission middleware aliases (for database-based roles)
            'spatie.role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->reportable(function (\Throwable $e) {
            if (app()->runningUnitTests()) {
                return false;
            }
        });

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

        // Avoid logging HttpException-based authorization responses during tests
        $exceptions->reportable(function (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            if (app()->runningUnitTests()) {
                return false;
            }
        });
    })
    ->create();

return $app;
