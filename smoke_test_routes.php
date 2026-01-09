<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$routes = [
    '/superadmin/login' => 'Superadmin Panel',
    '/admin/login' => 'Admin Panel', 
    '/manager/login' => 'Manager Panel',
    '/tenant/login' => 'Tenant Panel',
];

echo "=== SMOKE TEST: Panel Login Routes ===\n\n";

foreach ($routes as $uri => $name) {
    try {
        $request = Illuminate\Http\Request::create($uri, 'GET');
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        
        if ($status === 200) {
            echo "✓ $name ($uri): 200 OK\n";
        } elseif ($status >= 300 && $status < 400) {
            $location = $response->headers->get('Location', 'unknown');
            echo "→ $name ($uri): $status Redirect to $location\n";
        } elseif ($status === 500) {
            echo "✗ $name ($uri): 500 SERVER ERROR\n";
        } else {
            echo "? $name ($uri): $status\n";
        }
        
        $kernel->terminate($request, $response);
    } catch (Exception $e) {
        echo "✗ $name ($uri): EXCEPTION - " . get_class($e) . ": " . substr($e->getMessage(), 0, 150) . "\n";
    }
}

echo "\n=== Middleware Check ===\n";

// Check if middleware classes exist and are registered
$middlewareToCheck = [
    'subscription.check' => 'App\Http\Middleware\CheckSubscription',
    'hierarchical.access' => 'App\Http\Middleware\HierarchicalAccess',
];

foreach ($middlewareToCheck as $alias => $class) {
    if (class_exists($class)) {
        echo "✓ $alias ($class): Class exists\n";
    } else {
        echo "✗ $alias ($class): Class NOT FOUND\n";
    }
}

echo "\nDone.\n";
