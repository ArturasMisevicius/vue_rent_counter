<?php

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\EnsureHierarchicalAccess;
use App\Http\Middleware\EnsureTenantContext;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsSuperadmin;
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
        //
    })
    ->create();

return $app;
