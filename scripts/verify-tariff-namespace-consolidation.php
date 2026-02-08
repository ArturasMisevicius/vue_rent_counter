#!/usr/bin/env php
<?php

/**
 * TariffResource Namespace Consolidation Verification Script
 * 
 * This script verifies that TariffResource follows the Filament 4 namespace
 * consolidation pattern correctly.
 * 
 * Usage: php scripts/verify-tariff-namespace-consolidation.php
 */

require __DIR__ . '/../vendor/autoload.php';

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  TariffResource Namespace Consolidation Verification      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

$checks = [];
$passed = 0;
$failed = 0;

$resourceFile = __DIR__ . '/../app/Filament/Resources/TariffResource.php';
$content = file_get_contents($resourceFile);

// Check 1: No individual action imports
echo "Checking for individual action imports... ";
$hasIndividualImports = preg_match('/use Filament\\\\Tables\\\\Actions\\\\[A-Za-z]+;/', $content);
if (!$hasIndividualImports) {
    echo "✅ PASS\n";
    $checks[] = ['check' => 'No individual action imports', 'status' => 'PASS'];
    $passed++;
} else {
    echo "❌ FAIL\n";
    $checks[] = ['check' => 'No individual action imports', 'status' => 'FAIL'];
    $failed++;
}

// Check 2: Uses consolidated namespace
echo "Checking for consolidated namespace import... ";
$hasConsolidatedImport = preg_match('/use Filament\\\\Tables;/', $content);
if ($hasConsolidatedImport) {
    echo "✅ PASS\n";
    $checks[] = ['check' => 'Uses consolidated namespace', 'status' => 'PASS'];
    $passed++;
} else {
    echo "❌ FAIL\n";
    $checks[] = ['check' => 'Uses consolidated namespace', 'status' => 'FAIL'];
    $failed++;
}

// Check 3: Actions use Tables\Actions\ prefix
echo "Checking for Tables\\Actions\\ prefix usage... ";
$hasActionPrefix = preg_match('/Tables\\\\Actions\\\\[A-Za-z]+::make/', $content);
if ($hasActionPrefix) {
    echo "✅ PASS\n";
    $checks[] = ['check' => 'Actions use Tables\\Actions\\ prefix', 'status' => 'PASS'];
    $passed++;
} else {
    echo "❌ FAIL\n";
    $checks[] = ['check' => 'Actions use Tables\\Actions\\ prefix', 'status' => 'FAIL'];
    $failed++;
}

// Check 4: Columns use Tables\Columns\ prefix (in BuildsTariffTableColumns trait)
echo "Checking for Tables\\Columns\\ prefix usage... ";
$traitFile = __DIR__ . '/../app/Filament/Resources/TariffResource/Concerns/BuildsTariffTableColumns.php';
$traitContent = file_get_contents($traitFile);
$hasColumnPrefix = preg_match('/Tables\\\\Columns\\\\[A-Za-z]+::make/', $traitContent);
if ($hasColumnPrefix) {
    echo "✅ PASS\n";
    $checks[] = ['check' => 'Columns use Tables\\Columns\\ prefix', 'status' => 'PASS'];
    $passed++;
} else {
    echo "❌ FAIL\n";
    $checks[] = ['check' => 'Columns use Tables\\Columns\\ prefix', 'status' => 'FAIL'];
    $failed++;
}

// Check 5: No leftover individual column imports
echo "Checking for individual column imports... ";
$hasIndividualColumnImports = preg_match('/use Filament\\\\Tables\\\\Columns\\\\[A-Za-z]+;/', $traitContent);
if (!$hasIndividualColumnImports) {
    echo "✅ PASS\n";
    $checks[] = ['check' => 'No individual column imports', 'status' => 'PASS'];
    $passed++;
} else {
    echo "❌ FAIL\n";
    $checks[] = ['check' => 'No individual column imports', 'status' => 'FAIL'];
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
    echo "║  ✅ NAMESPACE CONSOLIDATION VERIFIED                      ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    exit(0);
} else {
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  ❌ NAMESPACE CONSOLIDATION ISSUES FOUND                  ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "Please review the failed checks above.\n";
    echo "\n";
    exit(1);
}
