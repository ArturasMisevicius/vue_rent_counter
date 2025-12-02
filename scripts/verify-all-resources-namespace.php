<?php

declare(strict_types=1);

/**
 * Comprehensive Namespace Consolidation Verification Script
 * 
 * This script verifies that all Filament resources follow the Filament 4 namespace
 * consolidation pattern, which reduces import clutter by 87.5% and improves code
 * maintainability.
 * 
 * Verification Checks:
 * 1. No individual action imports (EditAction, DeleteAction, etc.)
 * 2. Uses consolidated `use Filament\Tables;` import
 * 3. All actions use `Tables\Actions\` prefix
 * 4. No individual column imports (TextColumn, IconColumn, etc.)
 * 5. All columns use `Tables\Columns\` prefix
 * 6. No individual filter imports (SelectFilter, TernaryFilter, etc.)
 * 7. All filters use `Tables\Filters\` prefix (if filters exist)
 * 
 * Usage:
 *   php scripts/verify-all-resources-namespace.php
 * 
 * Exit Codes:
 *   0 - All resources pass verification
 *   1 - One or more resources fail verification
 * 
 * Related Documentation:
 * - .kiro/specs/6-filament-namespace-consolidation/requirements.md
 * - docs/filament/NAMESPACE_CONSOLIDATION_COMPLETE.md
 * - docs/testing/BATCH_4_VERIFICATION_GUIDE.md
 * 
 * @see \App\Filament\Resources\TariffResource Example of consolidated namespace usage
 * @see \App\Filament\Resources\FaqResource Example of consolidated namespace usage
 * 
 * @package Scripts
 * @author Development Team
 * @version 1.0.0
 * @since 2025-11-28
 */

/**
 * List of all Filament resources to verify.
 * 
 * This list should be kept in sync with actual resources in app/Filament/Resources/.
 * Resources are organized by functional area for clarity.
 * 
 * @var array<string> List of resource class names (without namespace)
 */
$resources = [
    // Property Management
    'PropertyResource',
    'BuildingResource',
    
    // Metering
    'MeterResource',
    'MeterReadingResource',
    
    // Billing
    'InvoiceResource',
    'TariffResource',
    'ProviderResource',
    
    // User & Organization Management
    'UserResource',
    'SubscriptionResource',
    'OrganizationResource',
    'OrganizationActivityLogResource',
    
    // Content & Localization
    'FaqResource',
    'LanguageResource',
    'TranslationResource',
];

/**
 * @var array<string, array{passed: bool, checks: array<string, bool>}> Verification results per resource
 */
$results = [];

/**
 * @var int Count of resources that passed all checks
 */
$totalPassed = 0;

/**
 * @var int Count of resources that failed one or more checks
 */
$totalFailed = 0;

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Filament Resources Namespace Consolidation Verification  ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

foreach ($resources as $resource) {
    $filePath = __DIR__ . "/../app/Filament/Resources/{$resource}.php";
    
    // Skip if resource file doesn't exist
    if (!file_exists($filePath)) {
        echo "⚠️  {$resource}: File not found\n";
        continue;
    }
    
    $content = file_get_contents($filePath);
    
    /**
     * @var array<string, bool> Verification checks for this resource
     */
    $checks = [];
    
    // Check 1: No individual action imports
    $hasIndividualActionImports = preg_match('/use Filament\\\\Tables\\\\Actions\\\\(EditAction|DeleteAction|ViewAction|CreateAction|BulkActionGroup|DeleteBulkAction);/', $content);
    $checks['no_individual_actions'] = !$hasIndividualActionImports;
    
    // Check 2: Has consolidated namespace import
    $hasConsolidatedImport = strpos($content, 'use Filament\Tables;') !== false;
    $checks['has_consolidated_import'] = $hasConsolidatedImport;
    
    // Check 3: Uses Tables\Actions\ prefix
    $usesActionsPrefix = preg_match('/Tables\\\\Actions\\\\(EditAction|DeleteAction|ViewAction|CreateAction|BulkActionGroup|DeleteBulkAction)/', $content);
    $checks['uses_actions_prefix'] = $usesActionsPrefix;
    
    // Check 4: No individual column imports
    $hasIndividualColumnImports = preg_match('/use Filament\\\\Tables\\\\Columns\\\\(TextColumn|IconColumn|BadgeColumn);/', $content);
    $checks['no_individual_columns'] = !$hasIndividualColumnImports;
    
    // Check 5: Uses Tables\Columns\ prefix
    $usesColumnsPrefix = preg_match('/Tables\\\\Columns\\\\(TextColumn|IconColumn|BadgeColumn)/', $content);
    $checks['uses_columns_prefix'] = $usesColumnsPrefix;
    
    // Check 6: No individual filter imports
    $hasIndividualFilterImports = preg_match('/use Filament\\\\Tables\\\\Filters\\\\(SelectFilter|TernaryFilter|Filter);/', $content);
    $checks['no_individual_filters'] = !$hasIndividualFilterImports;
    
    // Check 7: Uses Tables\Filters\ prefix (if filters exist)
    $hasFilters = preg_match('/->filters\(\[/', $content);
    if ($hasFilters) {
        $usesFiltersPrefix = preg_match('/Tables\\\\Filters\\\\(SelectFilter|TernaryFilter|Filter)/', $content);
        $checks['uses_filters_prefix'] = $usesFiltersPrefix;
    } else {
        $checks['uses_filters_prefix'] = true; // N/A if no filters
    }
    
    $allPassed = !in_array(false, $checks, true);
    $results[$resource] = [
        'passed' => $allPassed,
        'checks' => $checks,
    ];
    
    if ($allPassed) {
        echo "✅ {$resource}\n";
        $totalPassed++;
    } else {
        echo "❌ {$resource}\n";
        $totalFailed++;
        foreach ($checks as $check => $passed) {
            if (!$passed) {
                echo "   ⚠️  Failed: {$check}\n";
            }
        }
    }
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Verification Summary                                      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "Total Resources: " . count($resources) . "\n";
echo "Passed: {$totalPassed} ✅\n";
echo "Failed: {$totalFailed} ❌\n\n";

if ($totalFailed === 0) {
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ ALL RESOURCES VERIFIED                                ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    exit(0);
} else {
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  ❌ VERIFICATION FAILED                                    ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    exit(1);
}
