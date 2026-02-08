<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\Security\SecurityHeaderFactory;
use App\Services\Security\SecurityHeaderService;
use App\Services\Security\NonceGeneratorService;
use App\ValueObjects\SecurityNonce;
use Illuminate\Http\Request;

/**
 * Security Headers Performance Benchmark
 * 
 * Measures the performance improvements from our optimizations.
 */

echo "Security Headers Performance Benchmark\n";
echo "=====================================\n\n";

// Simulate Laravel app bootstrap (simplified)
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Benchmark nonce generation
echo "1. Nonce Generation Performance:\n";
$iterations = 1000;
$startTime = microtime(true);

for ($i = 0; $i < $iterations; $i++) {
    SecurityNonce::generate();
}

$duration = (microtime(true) - $startTime) * 1000;
$avgTime = $duration / $iterations;

echo "   Generated {$iterations} nonces in " . round($duration, 2) . "ms\n";
echo "   Average: " . round($avgTime, 3) . "ms per nonce\n";
echo "   Target: < 0.5ms per nonce " . ($avgTime < 0.5 ? "✓ PASS" : "✗ FAIL") . "\n\n";

// Benchmark header factory caching
echo "2. Header Factory Caching Performance:\n";

try {
    $factory = $app->make(SecurityHeaderFactory::class);
    $nonce = SecurityNonce::generate();
    
    // First call (cache miss)
    $startTime = microtime(true);
    $headers1 = $factory->createForContext('production', $nonce);
    $firstCallTime = (microtime(true) - $startTime) * 1000;
    
    // Subsequent calls (cache hit) - if optimized method exists
    if (method_exists($factory, 'createForContextOptimized')) {
        $startTime = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $headers2 = $factory->createForContextOptimized('production', $nonce);
        }
        $cachedCallsTime = (microtime(true) - $startTime) * 1000 / 100;
        
        echo "   First call (cache miss): " . round($firstCallTime, 3) . "ms\n";
        echo "   Cached calls average: " . round($cachedCallsTime, 3) . "ms\n";
        echo "   Improvement: " . round(($firstCallTime - $cachedCallsTime) / $firstCallTime * 100, 1) . "%\n";
        echo "   Target: < 1ms for cached calls " . ($cachedCallsTime < 1 ? "✓ PASS" : "✗ FAIL") . "\n\n";
    } else {
        echo "   Optimized method not available, using standard method\n";
        
        $startTime = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $headers2 = $factory->createForContext('production', $nonce);
        }
        $standardCallsTime = (microtime(true) - $startTime) * 1000 / 100;
        
        echo "   Standard calls average: " . round($standardCallsTime, 3) . "ms\n\n";
    }
} catch (Exception $e) {
    echo "   Error testing header factory: " . $e->getMessage() . "\n\n";
}

// Benchmark CSP template performance
echo "3. CSP Template Performance:\n";

$contexts = ['api', 'admin', 'tenant', 'production', 'development'];
$totalTime = 0;

foreach ($contexts as $context) {
    $startTime = microtime(true);
    
    for ($i = 0; $i < 50; $i++) {
        try {
            if (method_exists($factory, 'createForContextOptimized')) {
                $factory->createForContextOptimized($context, SecurityNonce::generate());
            } else {
                $factory->createForContext($context, SecurityNonce::generate());
            }
        } catch (Exception $e) {
            // Skip contexts that might not be fully implemented
            continue;
        }
    }
    
    $contextTime = (microtime(true) - $startTime) * 1000 / 50;
    $totalTime += $contextTime;
    
    echo "   {$context}: " . round($contextTime, 3) . "ms average\n";
}

$avgContextTime = $totalTime / count($contexts);
echo "   Overall average: " . round($avgContextTime, 3) . "ms\n";
echo "   Target: < 2ms per context " . ($avgContextTime < 2 ? "✓ PASS" : "✗ FAIL") . "\n\n";

// Memory usage test
echo "4. Memory Usage Test:\n";

$initialMemory = memory_get_usage(true);

// Simulate processing multiple requests
for ($i = 0; $i < 100; $i++) {
    $nonce = SecurityNonce::generate();
    
    try {
        if (method_exists($factory, 'createForContextOptimized')) {
            $factory->createForContextOptimized('production', $nonce);
        } else {
            $factory->createForContext('production', $nonce);
        }
    } catch (Exception $e) {
        // Continue on error
    }
}

$finalMemory = memory_get_usage(true);
$memoryIncrease = $finalMemory - $initialMemory;

echo "   Memory increase for 100 operations: " . number_format($memoryIncrease / 1024, 2) . " KB\n";
echo "   Target: < 500KB " . ($memoryIncrease < 500 * 1024 ? "✓ PASS" : "✗ FAIL") . "\n\n";

echo "Benchmark Complete!\n";
echo "===================\n";
echo "Peak memory usage: " . number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";
echo "Total execution time: " . round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2) . "ms\n";