<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a test request to the superadmin panel
$request = Illuminate\Http\Request::create('/superadmin', 'GET');

try {
    $response = $kernel->handle($request);
    
    echo "Status Code: " . $response->getStatusCode() . "\n";
    echo "Content Type: " . $response->headers->get('Content-Type') . "\n";
    
    if ($response->getStatusCode() === 200) {
        echo "✅ Superadmin panel is accessible!\n";
    } elseif ($response->getStatusCode() === 302) {
        echo "🔄 Redirected to: " . $response->headers->get('Location') . "\n";
        echo "✅ Panel is working (redirect to login expected)\n";
    } else {
        echo "❌ Error accessing superadmin panel\n";
        echo "Response content: " . substr($response->getContent(), 0, 500) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    if (str_contains($e->getMessage(), 'filament-panels')) {
        echo "\n🔧 Filament panels view hint issue detected!\n";
        echo "This suggests the Filament service providers aren't loading correctly.\n";
    }
}

$kernel->terminate($request, $response ?? null);