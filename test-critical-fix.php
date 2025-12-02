<?php

/**
 * Critical Fix Verification Script
 * Tests login functionality and homepage access after auth fixes
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CRITICAL FIX VERIFICATION ===\n\n";

// Test 1: Check if login route is accessible
echo "Test 1: Login Route Accessibility\n";
try {
    $response = $app->make('router')->dispatch(
        Illuminate\Http\Request::create('/login', 'GET')
    );
    
    if ($response->getStatusCode() === 200) {
        echo "✅ PASS: Login page accessible (200 OK)\n";
    } else {
        echo "❌ FAIL: Login page returned " . $response->getStatusCode() . "\n";
    }
} catch (\Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Check if homepage is accessible for guests
echo "Test 2: Homepage Accessibility (Guest)\n";
try {
    $startTime = microtime(true);
    
    $response = $app->make('router')->dispatch(
        Illuminate\Http\Request::create('/', 'GET')
    );
    
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    if ($response->getStatusCode() === 200 && $duration < 1000) {
        echo "✅ PASS: Homepage accessible in {$duration}ms (no timeout)\n";
    } else {
        echo "❌ FAIL: Homepage returned " . $response->getStatusCode() . " in {$duration}ms\n";
    }
} catch (\Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Check if middleware is properly configured
echo "Test 3: Middleware Configuration\n";
try {
    $middleware = $app->make('router')->getMiddleware();
    
    if (isset($middleware['subscription.check'])) {
        echo "✅ PASS: subscription.check middleware registered\n";
    } else {
        echo "❌ FAIL: subscription.check middleware not found\n";
    }
    
    if (isset($middleware['auth'])) {
        echo "✅ PASS: auth middleware registered\n";
    } else {
        echo "❌ FAIL: auth middleware not found\n";
    }
} catch (\Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Check if HierarchicalScope has recursion protection
echo "Test 4: HierarchicalScope Recursion Protection\n";
try {
    $scopeFile = file_get_contents(__DIR__ . '/app/Scopes/HierarchicalScope.php');
    
    if (strpos($scopeFile, 'self::$isApplying') !== false) {
        echo "✅ PASS: Recursion guard found\n";
    } else {
        echo "❌ FAIL: Recursion guard not found\n";
    }
    
    if (strpos($scopeFile, 'if ($user === null)') !== false) {
        echo "✅ PASS: Guest protection found\n";
    } else {
        echo "❌ FAIL: Guest protection not found\n";
    }
    
    if (strpos($scopeFile, 'if ($model instanceof User)') !== false) {
        echo "✅ PASS: User model skip found\n";
    } else {
        echo "❌ FAIL: User model skip not found\n";
    }
} catch (\Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Check if CheckSubscriptionStatus skips auth routes
echo "Test 5: CheckSubscriptionStatus Auth Route Skip\n";
try {
    $middlewareFile = file_get_contents(__DIR__ . '/app/Http/Middleware/CheckSubscriptionStatus.php');
    
    if (strpos($middlewareFile, "shouldBypassCheck") !== false) {
        echo "✅ PASS: Bypass check method found\n";
    } else {
        echo "❌ FAIL: Bypass check method not found\n";
    }
    
    if (strpos($middlewareFile, "BYPASS_ROUTES") !== false) {
        echo "✅ PASS: Bypass routes constant found\n";
    } else {
        echo "❌ FAIL: Bypass routes constant not found\n";
    }
    
    if (strpos($middlewareFile, "'login'") !== false && 
        strpos($middlewareFile, "'register'") !== false && 
        strpos($middlewareFile, "'logout'") !== false) {
        echo "✅ PASS: All auth routes in bypass list\n";
    } else {
        echo "❌ FAIL: Not all auth routes in bypass list\n";
    }
} catch (\Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 6: Check if CSRF token is present in login form
echo "Test 6: CSRF Token in Login Form\n";
try {
    $loginView = file_get_contents(__DIR__ . '/resources/views/auth/login.blade.php');
    
    if (strpos($loginView, '@csrf') !== false) {
        echo "✅ PASS: CSRF token directive found\n";
    } else {
        echo "❌ FAIL: CSRF token directive not found\n";
    }
} catch (\Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "\n";
}

echo "\n=== VERIFICATION COMPLETE ===\n";
