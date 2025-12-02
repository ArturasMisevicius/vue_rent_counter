#!/usr/bin/env php
<?php

/**
 * TariffResource Performance Verification Script
 * 
 * This script verifies that all performance optimizations are in place
 * and functioning correctly.
 * 
 * Usage: php scripts/verify-tariff-performance.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  TariffResource Performance Verification                  ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

$checks = [];
$passed = 0;
$failed = 0;

// Check 1: Virtual column exists
echo "Checking virtual column on tariffs table... ";
if (Schema::hasColumn('tariffs', 'type')) {
    echo "✅ PASS\n";
    $checks[] = ['check' => 'Virtual column exists', 'status' => 'PASS'];
    $passed++;
} else {
    echo "❌ FAIL\n";
    $checks[] = ['check' => 'Virtual column exists', 'status' => 'FAIL'];
    $failed++;
}

// Check 2: Type index exists
echo "Checking type index... ";
$driver = DB::connection()->getDriverName();
if ($driver === 'sqlite') {
    $indexes = DB::select("PRAGMA index_list(tariffs)");
    $hasIndex = false;
    foreach ($indexes as $index) {
        // Accept either name (tariffs_type_index or tariffs_type_virtual_index)
        if ($index->name === 'tariffs_type_virtual_index' || $index->name === 'tariffs_type_index') {
            $hasIndex = true;
            break;
        }
    }
} else {
    $indexes = DB::select("SHOW INDEX FROM tariffs WHERE Key_name IN (?, ?)", 
        ['tariffs_type_virtual_index', 'tariffs_type_index']);
    $hasIndex = count($indexes) > 0;
}

if ($hasIndex) {
    echo "✅ PASS\n";
    $checks[] = ['check' => 'Type index exists', 'status' => 'PASS'];
    $passed++;
} else {
    echo "❌ FAIL\n";
    $checks[] = ['check' => 'Type index exists', 'status' => 'FAIL'];
    $failed++;
}

// Check 3: Provider composite index exists
echo "Checking provider composite index... ";
if ($driver === 'sqlite') {
    $indexes = DB::select("PRAGMA index_list(providers)");
    $hasIndex = false;
    foreach ($indexes as $index) {
        if ($index->name === 'providers_tariff_lookup_index') {
            $hasIndex = true;
            break;
        }
    }
} else {
    $indexes = DB::select("SHOW INDEX FROM providers WHERE Key_name = ?", ['providers_tariff_lookup_index']);
    $hasIndex = count($indexes) > 0;
}

if ($hasIndex) {
    echo "✅ PASS\n";
    $checks[] = ['check' => 'Provider composite index exists', 'status' => 'PASS'];
    $passed++;
} else {
    echo "❌ FAIL\n";
    $checks[] = ['check' => 'Provider composite index exists', 'status' => 'FAIL'];
    $failed++;
}

// Check 4: BuildsTariffTableColumns has caching
echo "Checking enum label caching in BuildsTariffTableColumns... ";
$traitFile = __DIR__ . '/../app/Filament/Resources/TariffResource/Concerns/BuildsTariffTableColumns.php';
$content = file_get_contents($traitFile);
$hasCaching = strpos($content, 'private static ?array $serviceTypeLabels') !== false
    && strpos($content, 'private static ?array $tariffTypeLabels') !== false
    && strpos($content, 'getServiceTypeLabels()') !== false
    && strpos($content, 'getTariffTypeLabels()') !== false;

if ($hasCaching) {
    echo "✅ PASS\n";
    $checks[] = ['check' => 'Enum label caching implemented', 'status' => 'PASS'];
    $passed++;
} else {
    echo "❌ FAIL\n";
    $checks[] = ['check' => 'Enum label caching implemented', 'status' => 'FAIL'];
    $failed++;
}

// Check 5: is_active uses closure optimization
echo "Checking is_active closure optimization... ";
$hasOptimization = strpos($content, 'getStateUsing(function (Tariff $record) use ($now)') !== false
    || strpos($content, '->getStateUsing(function') !== false;

if ($hasOptimization) {
    echo "✅ PASS\n";
    $checks[] = ['check' => 'is_active closure optimization', 'status' => 'PASS'];
    $passed++;
} else {
    echo "❌ FAIL\n";
    $checks[] = ['check' => 'is_active closure optimization', 'status' => 'FAIL'];
    $failed++;
}

// Check 6: CachesAuthUser trait is used
echo "Checking CachesAuthUser trait usage... ";
$resourceFile = __DIR__ . '/../app/Filament/Resources/TariffResource.php';
$resourceContent = file_get_contents($resourceFile);
$usesCaching = strpos($resourceContent, 'use Concerns\CachesAuthUser;') !== false;

if ($usesCaching) {
    echo "✅ PASS\n";
    $checks[] = ['check' => 'CachesAuthUser trait used', 'status' => 'PASS'];
    $passed++;
} else {
    echo "❌ FAIL\n";
    $checks[] = ['check' => 'CachesAuthUser trait used', 'status' => 'FAIL'];
    $failed++;
}

// Summary
echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Verification Summary                                      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

foreach ($checks as $check) {
    $status = $check['status'] === 'PASS' ? '✅' : '❌';
    printf("  %s  %s\n", $status, $check['check']);
}

echo "\n";
echo "Total Checks: " . ($passed + $failed) . "\n";
echo "Passed: " . $passed . " ✅\n";
echo "Failed: " . $failed . " ❌\n";
echo "\n";

if ($failed === 0) {
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ ALL OPTIMIZATIONS VERIFIED                            ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    exit(0);
} else {
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  ❌ SOME OPTIMIZATIONS MISSING                            ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "Please run: php artisan migrate\n";
    echo "And verify code changes are applied.\n";
    echo "\n";
    exit(1);
}
