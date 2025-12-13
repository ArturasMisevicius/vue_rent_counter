<?php

require_once 'vendor/autoload.php';

use App\Services\ServiceValidationEngine;
use App\Models\MeterReading;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Validation Engine Performance Test ===" . PHP_EOL;

try {
    // Enable query logging
    DB::enableQueryLog();

    // Get validation engine
    $engine = app(ServiceValidationEngine::class);

    // Get some test readings
    $readings = MeterReading::with(['meter'])->limit(5)->get();

    if ($readings->count() > 0) {
        echo "Testing batch validation with {$readings->count()} readings..." . PHP_EOL;
        
        // Clear query log
        DB::flushQueryLog();
        $startTime = microtime(true);
        
        // Run batch validation
        $result = $engine->batchValidateReadings($readings);
        
        $endTime = microtime(true);
        $queryCount = count(DB::getQueryLog());
        
        echo "Results:" . PHP_EOL;
        echo "- Duration: " . round(($endTime - $startTime) * 1000, 2) . "ms" . PHP_EOL;
        echo "- Queries: {$queryCount}" . PHP_EOL;
        echo "- Queries per reading: " . round($queryCount / $readings->count(), 2) . PHP_EOL;
        echo "- Total readings processed: {$result['total_readings']}" . PHP_EOL;
        echo "- Valid readings: {$result['valid_readings']}" . PHP_EOL;
        
        if ($queryCount / $readings->count() < 2.0) {
            echo "✅ Query optimization SUCCESS (< 2 queries per reading)" . PHP_EOL;
        } else {
            echo "❌ Query optimization needs improvement" . PHP_EOL;
        }
        
        // Show performance metrics if available
        if (isset($result['performance_metrics'])) {
            $metrics = $result['performance_metrics'];
            echo "Performance Metrics:" . PHP_EOL;
            echo "- Memory peak: " . ($metrics['memory_peak_mb'] ?? 'N/A') . "MB" . PHP_EOL;
            echo "- Cache hits: " . ($metrics['cache_hits'] ?? 'N/A') . PHP_EOL;
        }
        
    } else {
        echo "No meter readings found for testing" . PHP_EOL;
        echo "Creating sample data..." . PHP_EOL;
        
        // This would require factories to be set up
        echo "Please run: php artisan db:seed to create sample data" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}

echo "=== Test Complete ===" . PHP_EOL;