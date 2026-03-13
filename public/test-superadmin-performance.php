<?php

/**
 * Superadmin Panel Performance Test
 * 
 * This script tests the superadmin panel performance and identifies
 * potential bottlenecks that could cause timeouts.
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Superadmin Panel Performance Test ===\n\n";

// Test 1: Database Connection
echo "1. Testing database connection...\n";
$start = microtime(true);
try {
    DB::connection()->getPdo();
    $dbTime = round((microtime(true) - $start) * 1000, 2);
    echo "   ✓ Database connected in {$dbTime}ms\n";
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
}

// Test 2: User Count Query
echo "\n2. Testing user count query...\n";
$start = microtime(true);
try {
    $userCount = App\Models\User::count();
    $userTime = round((microtime(true) - $start) * 1000, 2);
    echo "   ✓ Found {$userCount} users in {$userTime}ms\n";
} catch (Exception $e) {
    echo "   ✗ User query failed: " . $e->getMessage() . "\n";
}

// Test 3: Cache System
echo "\n3. Testing cache system...\n";
$start = microtime(true);
try {
    Cache::put('performance_test', 'ok', 10);
    $result = Cache::get('performance_test');
    Cache::forget('performance_test');
    $cacheTime = round((microtime(true) - $start) * 1000, 2);
    
    if ($result === 'ok') {
        echo "   ✓ Cache system working in {$cacheTime}ms\n";
    } else {
        echo "   ✗ Cache system not working properly\n";
    }
} catch (Exception $e) {
    echo "   ✗ Cache test failed: " . $e->getMessage() . "\n";
}

// Test 4: Translation System
echo "\n4. Testing translation system...\n";
$start = microtime(true);
try {
    $translation = __('app.nav_groups.system_management');
    $transTime = round((microtime(true) - $start) * 1000, 2);
    echo "   ✓ Translation loaded: '{$translation}' in {$transTime}ms\n";
} catch (Exception $e) {
    echo "   ✗ Translation test failed: " . $e->getMessage() . "\n";
}

// Test 5: Memory Usage
echo "\n5. Memory usage check...\n";
$memoryUsage = round(memory_get_usage(true) / 1024 / 1024, 2);
$memoryPeak = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
echo "   Current memory: {$memoryUsage}MB\n";
echo "   Peak memory: {$memoryPeak}MB\n";

if ($memoryPeak > 128) {
    echo "   ⚠ High memory usage detected\n";
} else {
    echo "   ✓ Memory usage is acceptable\n";
}

echo "\n=== Performance Test Complete ===\n";
echo "If all tests pass, the superadmin panel should load without timeouts.\n";
echo "Access the panel at: " . url('/superadmin') . "\n";