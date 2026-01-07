<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "🔐 Testing Superadmin Panel Login Flow\n";
echo "=====================================\n\n";

// Test 1: Access superadmin panel without authentication (should redirect to login)
echo "1. Testing unauthenticated access...\n";
$request = Illuminate\Http\Request::create('/superadmin', 'GET');

try {
    $response = $kernel->handle($request);
    
    if ($response->getStatusCode() === 302 && str_contains($response->headers->get('Location'), '/login')) {
        echo "   ✅ Correctly redirects to login when unauthenticated\n";
    } else {
        echo "   ❌ Unexpected response: " . $response->getStatusCode() . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

// Test 2: Check login page accessibility
echo "\n2. Testing login page access...\n";
$loginRequest = Illuminate\Http\Request::create('/superadmin/login', 'GET');

try {
    $loginResponse = $kernel->handle($loginRequest);
    
    if ($loginResponse->getStatusCode() === 200) {
        echo "   ✅ Login page is accessible\n";
        
        // Check if the response contains login form elements
        $content = $loginResponse->getContent();
        if (str_contains($content, 'email') && str_contains($content, 'password')) {
            echo "   ✅ Login form elements detected\n";
        } else {
            echo "   ⚠️  Login form elements not clearly detected\n";
        }
    } else {
        echo "   ❌ Login page error: " . $loginResponse->getStatusCode() . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Login page exception: " . $e->getMessage() . "\n";
}

// Test 3: Verify superadmin user exists and has correct role
echo "\n3. Verifying superadmin user...\n";
try {
    $user = \App\Models\User::where('email', 'superadmin@example.com')->first();
    
    if ($user) {
        echo "   ✅ Superadmin user found: {$user->name}\n";
        echo "   ✅ Email: {$user->email}\n";
        echo "   ✅ Role: {$user->role->value}\n";
        
        if ($user->role === \App\Enums\UserRole::SUPERADMIN) {
            echo "   ✅ User has correct SUPERADMIN role\n";
        } else {
            echo "   ❌ User does not have SUPERADMIN role\n";
        }
    } else {
        echo "   ❌ Superadmin user not found\n";
    }
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

// Test 4: Check Filament service providers are loaded
echo "\n4. Checking Filament service providers...\n";
try {
    $providers = app()->getLoadedProviders();
    
    $filamentProviders = [
        'Filament\FilamentServiceProvider',
        'App\Providers\Filament\SuperadminPanelProvider',
        'BladeUI\Icons\BladeIconsServiceProvider',
        'BladeUI\Heroicons\BladeHeroiconsServiceProvider',
    ];
    
    foreach ($filamentProviders as $provider) {
        if (isset($providers[$provider])) {
            echo "   ✅ {$provider} loaded\n";
        } else {
            echo "   ❌ {$provider} NOT loaded\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Provider check error: " . $e->getMessage() . "\n";
}

echo "\n🎉 Superadmin Panel Test Complete!\n";
echo "=====================================\n";
echo "The panel should now be accessible at: /superadmin\n";
echo "Login with: superadmin@example.com / password\n";

$kernel->terminate($request, $response ?? null);