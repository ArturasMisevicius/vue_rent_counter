<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Create the application with proper bootstrap sequence
$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__."/../routes/web.php",
        api: __DIR__."/../routes/api.php",
        commands: __DIR__."/../routes/console.php",
        health: "/up",
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
        
        $middleware->alias([
            // Tenant context middleware
            'tenant.context' => \App\Http\Middleware\EnsureTenantContext::class,
            'tenant.set' => \App\Http\Middleware\SetTenantContext::class,
            
            // Role-based access control
            'superadmin' => \App\Http\Middleware\EnsureUserIsSuperadmin::class,
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            
            // Subscription and hierarchical access (CRITICAL for admin/manager/tenant routes)
            'subscription.check' => \App\Http\Middleware\CheckSubscriptionStatus::class,
            'hierarchical.access' => \App\Http\Middleware\EnsureHierarchicalAccess::class,
            
            // Spatie Laravel Permission middleware aliases (for database-based roles)
            'spatie.role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();

return $app;
