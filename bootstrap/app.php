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
            'tenant.context' => \App\Http\Middleware\EnsureTenantContext::class,
            'tenant.set' => \App\Http\Middleware\SetTenantContext::class,
            'superadmin' => \App\Http\Middleware\EnsureUserIsSuperadmin::class,
            // Spatie Laravel Permission middleware aliases
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();

// Ensure proper Laravel bootstrap sequence for Laravel 12
// This is critical - Laravel 12 requires manual bootstrapping in some cases
$bootstrappers = [
    \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
    \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
    \Illuminate\Foundation\Bootstrap\HandleExceptions::class,
    \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
    \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
    \Illuminate\Foundation\Bootstrap\BootProviders::class,
];

foreach ($bootstrappers as $bootstrapper) {
    if (class_exists($bootstrapper)) {
        try {
            $instance = new $bootstrapper();
            if (method_exists($instance, 'bootstrap')) {
                $instance->bootstrap($app);
            }
        } catch (Exception $e) {
            // Some bootstrappers may fail if already run, continue
            continue;
        }
    }
}

// Initialize facades with the application instance
// This is critical for Filament facades to work properly
\Illuminate\Support\Facades\Facade::setFacadeApplication($app);

return $app;
