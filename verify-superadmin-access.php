<?php

declare(strict_types=1);

/**
 * Superadmin Access Verification Script
 * 
 * This script verifies that the superadmin user can properly access the Filament panel.
 * It simulates the actual panel access check that Filament performs.
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Enums\UserRole;
use Filament\Facades\Filament;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== SUPERADMIN ACCESS VERIFICATION ===" . PHP_EOL;
echo "Testing panel access for tenant3@test.com" . PHP_EOL;
echo "--------------------------------------" . PHP_EOL;

try {
    // Find the user
    $user = User::where('email', 'tenant3@test.com')->first();
    
    if (!$user) {
        echo "❌ ERROR: User tenant3@test.com not found!" . PHP_EOL;
        exit(1);
    }
    
    echo "✅ User found: {$user->name} ({$user->email})" . PHP_EOL;
    echo "Role: {$user->role->value}" . PHP_EOL;
    echo "is_super_admin: " . ($user->is_super_admin ? 'true' : 'false') . PHP_EOL;
    echo "is_active: " . ($user->is_active ? 'true' : 'false') . PHP_EOL;
    echo PHP_EOL;
    
    // Test 1: Check role enum
    echo "🧪 Test 1: Role enum check" . PHP_EOL;
    $isCorrectRole = $user->role === UserRole::SUPERADMIN;
    echo "user->role === UserRole::SUPERADMIN: " . ($isCorrectRole ? '✅ PASS' : '❌ FAIL') . PHP_EOL;
    
    // Test 2: Check is_super_admin flag
    echo PHP_EOL . "🧪 Test 2: Super admin flag check" . PHP_EOL;
    echo "user->is_super_admin: " . ($user->is_super_admin ? '✅ PASS' : '❌ FAIL') . PHP_EOL;
    
    // Test 3: Check user is active
    echo PHP_EOL . "🧪 Test 3: User active check" . PHP_EOL;
    $isActive = $user->is_active && $user->suspended_at === null;
    echo "user is active and not suspended: " . ($isActive ? '✅ PASS' : '❌ FAIL') . PHP_EOL;
    
    // Test 4: UserRoleService check
    echo PHP_EOL . "🧪 Test 4: UserRoleService check" . PHP_EOL;
    $roleService = app(\App\Services\UserRoleService::class);
    $isSuperadmin = $roleService->isSuperadmin($user);
    echo "UserRoleService->isSuperadmin(): " . ($isSuperadmin ? '✅ PASS' : '❌ FAIL') . PHP_EOL;
    
    // Test 5: PanelAccessService check
    echo PHP_EOL . "🧪 Test 5: PanelAccessService check" . PHP_EOL;
    $panelService = app(\App\Services\PanelAccessService::class);
    $canAccessSuperadmin = $panelService->canAccessSuperadminPanel($user);
    echo "PanelAccessService->canAccessSuperadminPanel(): " . ($canAccessSuperadmin ? '✅ PASS' : '❌ FAIL') . PHP_EOL;
    
    // Test 6: Middleware simulation
    echo PHP_EOL . "🧪 Test 6: Middleware simulation" . PHP_EOL;
    $middleware = new \App\Http\Middleware\EnsureUserIsSuperadmin();
    
    // Create a mock request
    $request = \Illuminate\Http\Request::create('/superadmin/dashboard');
    $request->setUserResolver(function () use ($user) {
        return $user;
    });
    
    try {
        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        });
        echo "EnsureUserIsSuperadmin middleware: " . ($response->getContent() === 'OK' ? '✅ PASS' : '❌ FAIL') . PHP_EOL;
    } catch (\Exception $e) {
        echo "EnsureUserIsSuperadmin middleware: ❌ FAIL - " . $e->getMessage() . PHP_EOL;
    }
    
    // Final result
    echo PHP_EOL . "=== FINAL RESULT ===" . PHP_EOL;
    if ($isCorrectRole && $user->is_super_admin && $isActive && $isSuperadmin && $canAccessSuperadmin) {
        echo "🎉 SUCCESS: User should be able to access /superadmin/dashboard" . PHP_EOL;
        echo PHP_EOL;
        echo "Next steps:" . PHP_EOL;
        echo "1. Open your browser" . PHP_EOL;
        echo "2. Go to: http://localhost/superadmin/dashboard" . PHP_EOL;
        echo "3. Login with:" . PHP_EOL;
        echo "   Email: tenant3@test.com" . PHP_EOL;
        echo "   Password: password" . PHP_EOL;
    } else {
        echo "❌ FAILED: User still cannot access the superadmin panel" . PHP_EOL;
        echo "Check the failed tests above for details." . PHP_EOL;
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(1);
}