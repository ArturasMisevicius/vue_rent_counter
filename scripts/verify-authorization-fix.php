#!/usr/bin/env php
<?php

/**
 * Authorization Fix Verification Script
 * 
 * This script verifies that the authorization fix is properly implemented
 * and that TENANT users cannot access admin panels.
 * 
 * Usage: php scripts/verify-authorization-fix.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\UserRole;
use App\Models\User;
use Filament\Facades\Filament;

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Authorization Fix Verification                            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

$passed = 0;
$failed = 0;

function test(string $description, callable $test): void
{
    global $passed, $failed;
    
    try {
        $result = $test();
        if ($result) {
            echo "✅ PASS: {$description}\n";
            $passed++;
        } else {
            echo "❌ FAIL: {$description}\n";
            $failed++;
        }
    } catch (\Exception $e) {
        echo "❌ ERROR: {$description} - {$e->getMessage()}\n";
        $failed++;
    }
}

// Get admin panel
$panel = Filament::getPanel('admin');

echo "Running authorization tests...\n\n";

// Test 1: SUPERADMIN can access admin panel
test('SUPERADMIN can access admin panel', function () use ($panel) {
    $user = User::factory()->superadmin()->make(['is_active' => true]);
    return $user->canAccessPanel($panel) === true;
});

// Test 2: ADMIN can access admin panel
test('ADMIN can access admin panel', function () use ($panel) {
    $user = User::factory()->admin(1)->make(['is_active' => true]);
    return $user->canAccessPanel($panel) === true;
});

// Test 3: MANAGER can access admin panel
test('MANAGER can access admin panel', function () use ($panel) {
    $user = User::factory()->manager(1)->make(['is_active' => true]);
    return $user->canAccessPanel($panel) === true;
});

// Test 4: TENANT cannot access admin panel (CRITICAL)
test('TENANT cannot access admin panel (CRITICAL)', function () use ($panel) {
    $user = User::factory()->tenant(1, 1, 1)->make(['is_active' => true]);
    return $user->canAccessPanel($panel) === false;
});

// Test 5: Inactive SUPERADMIN cannot access admin panel
test('Inactive SUPERADMIN cannot access admin panel', function () use ($panel) {
    $user = User::factory()->superadmin()->make(['is_active' => false]);
    return $user->canAccessPanel($panel) === false;
});

// Test 6: Inactive ADMIN cannot access admin panel
test('Inactive ADMIN cannot access admin panel', function () use ($panel) {
    $user = User::factory()->admin(1)->make(['is_active' => false]);
    return $user->canAccessPanel($panel) === false;
});

// Test 7: Inactive MANAGER cannot access admin panel
test('Inactive MANAGER cannot access admin panel', function () use ($panel) {
    $user = User::factory()->manager(1)->make(['is_active' => false]);
    return $user->canAccessPanel($panel) === false;
});

// Test 8: Inactive TENANT cannot access admin panel
test('Inactive TENANT cannot access admin panel', function () use ($panel) {
    $user = User::factory()->tenant(1, 1, 1)->make(['is_active' => false]);
    return $user->canAccessPanel($panel) === false;
});

// Test 9: Role helper methods work correctly
test('isSuperadmin() helper works correctly', function () {
    $user = User::factory()->superadmin()->make();
    return $user->isSuperadmin() === true && 
           $user->isAdmin() === false && 
           $user->isManager() === false && 
           $user->isTenantUser() === false;
});

test('isAdmin() helper works correctly', function () {
    $user = User::factory()->admin(1)->make();
    return $user->isSuperadmin() === false && 
           $user->isAdmin() === true && 
           $user->isManager() === false && 
           $user->isTenantUser() === false;
});

test('isManager() helper works correctly', function () {
    $user = User::factory()->manager(1)->make();
    return $user->isSuperadmin() === false && 
           $user->isAdmin() === false && 
           $user->isManager() === true && 
           $user->isTenantUser() === false;
});

test('isTenantUser() helper works correctly', function () {
    $user = User::factory()->tenant(1, 1, 1)->make();
    return $user->isSuperadmin() === false && 
           $user->isAdmin() === false && 
           $user->isManager() === false && 
           $user->isTenantUser() === true;
});

// Test 10: Verify canAccessPanel method exists and is not bypassed
test('canAccessPanel() method is not bypassed', function () use ($panel) {
    $user = User::factory()->tenant(1, 1, 1)->make(['is_active' => true]);
    $reflection = new \ReflectionMethod($user, 'canAccessPanel');
    $source = file_get_contents($reflection->getFileName());
    
    // Check that the method doesn't just return true
    $methodStart = $reflection->getStartLine();
    $methodEnd = $reflection->getEndLine();
    $lines = array_slice(explode("\n", $source), $methodStart - 1, $methodEnd - $methodStart + 1);
    $methodSource = implode("\n", $lines);
    
    // Should NOT contain "return true;" as the only logic
    $hasProperLogic = strpos($methodSource, 'is_active') !== false &&
                      strpos($methodSource, 'in_array') !== false;
    
    return $hasProperLogic && $user->canAccessPanel($panel) === false;
});

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Test Results                                              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "✅ Passed: {$passed}\n";
echo "❌ Failed: {$failed}\n";
echo "\n";

if ($failed === 0) {
    echo "🎉 All authorization tests passed!\n";
    echo "✅ Authorization fix is properly implemented.\n";
    echo "✅ TENANT users cannot access admin panels.\n";
    echo "✅ Inactive users are properly blocked.\n";
    echo "\n";
    exit(0);
} else {
    echo "⚠️  Some tests failed!\n";
    echo "❌ Authorization may not be properly implemented.\n";
    echo "❌ Please review the failed tests above.\n";
    echo "\n";
    exit(1);
}
