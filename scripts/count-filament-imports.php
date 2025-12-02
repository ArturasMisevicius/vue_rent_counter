<?php

/**
 * Filament Namespace Consolidation - Import Count Analysis Script
 * 
 * This script analyzes all Filament resources in the application and generates
 * a comprehensive report on namespace import patterns. It identifies resources
 * that use individual imports vs. consolidated namespace imports.
 * 
 * Purpose:
 * - Assess which resources need namespace consolidation
 * - Quantify potential code reduction benefits
 * - Prioritize consolidation efforts
 * - Track consolidation progress
 * 
 * Analysis Performed:
 * - Individual Filament\Tables\Actions\* imports
 * - Individual Filament\Tables\Columns\* imports
 * - Individual Filament\Tables\Filters\* imports
 * - Consolidated `use Filament\Tables;` import presence
 * - Table import verification
 * 
 * Output:
 * - Summary statistics (consolidated vs. needs consolidation)
 * - Detailed breakdown per resource
 * - Priority recommendations based on import count
 * - Estimated effort for remaining consolidation work
 * 
 * Usage:
 *   php scripts/count-filament-imports.php
 * 
 * Related Documentation:
 * - .kiro/specs/6-filament-namespace-consolidation/tasks.md
 * - docs/filament/NAMESPACE_CONSOLIDATION_ASSESSMENT.md
 * 
 * @package VilniusBilling\Scripts
 * @author  Kiro AI Assistant
 * @version 1.0.0
 * @since   2024-11-29
 */

// Configuration
$resourcesPath = __DIR__ . '/../app/Filament/Resources';
$resources = [];

// Scan for all Filament resource files
$files = glob($resourcesPath . '/*Resource.php');

// Analyze each resource file
foreach ($files as $file) {
    $resourceName = basename($file, '.php');
    $content = file_get_contents($file);
    
    // Count individual imports using regex patterns
    // Pattern matches: use Filament\Tables\Actions\{ClassName};
    $actionImports = preg_match_all('/^use Filament\\\\Tables\\\\Actions\\\\[^;]+;/m', $content);
    $columnImports = preg_match_all('/^use Filament\\\\Tables\\\\Columns\\\\[^;]+;/m', $content);
    $filterImports = preg_match_all('/^use Filament\\\\Tables\\\\Filters\\\\[^;]+;/m', $content);
    
    // Check for consolidated namespace import
    // Pattern matches: use Filament\Tables;
    $hasConsolidated = preg_match('/^use Filament\\\\Tables;$/m', $content) === 1;
    
    // Check for Table import (always required separately)
    // Pattern matches: use Filament\Tables\Table;
    $hasTableImport = preg_match('/^use Filament\\\\Tables\\\\Table;$/m', $content) === 1;
    
    // Calculate totals
    $totalIndividual = $actionImports + $columnImports + $filterImports;
    
    // Determine consolidation status
    // A resource is considered consolidated if:
    // 1. It has zero individual imports, AND
    // 2. It uses the consolidated namespace import
    $status = ($totalIndividual === 0 && $hasConsolidated) 
        ? 'CONSOLIDATED' 
        : 'NEEDS CONSOLIDATION';
    
    // Store analysis results
    $resources[$resourceName] = [
        'file' => basename($file),
        'actions' => $actionImports,
        'columns' => $columnImports,
        'filters' => $filterImports,
        'total_individual' => $totalIndividual,
        'has_consolidated' => $hasConsolidated,
        'has_table_import' => $hasTableImport,
        'status' => $status
    ];
}

// Sort by total individual imports (descending)
uasort($resources, function($a, $b) {
    return $b['total_individual'] <=> $a['total_individual'];
});

// Generate report
echo "================================================================================\n";
echo "FILAMENT NAMESPACE CONSOLIDATION - IMPORT COUNT REPORT\n";
echo "================================================================================\n";
echo "Generated: " . date('Y-m-d H:i:s') . "\n";
echo "Total Resources Analyzed: " . count($resources) . "\n";
echo "================================================================================\n\n";

// Summary statistics
$consolidated = array_filter($resources, fn($r) => $r['status'] === 'CONSOLIDATED');
$needsConsolidation = array_filter($resources, fn($r) => $r['status'] === 'NEEDS CONSOLIDATION');

echo "SUMMARY\n";
echo "-------\n";
echo "✅ Already Consolidated: " . count($consolidated) . " resources\n";
echo "⏭️  Needs Consolidation: " . count($needsConsolidation) . " resources\n";
echo "\n";

if (count($needsConsolidation) > 0) {
    echo "RESOURCES NEEDING CONSOLIDATION\n";
    echo "-------------------------------\n";
    foreach ($needsConsolidation as $name => $data) {
        echo sprintf(
            "❌ %s: %d individual imports (Actions: %d, Columns: %d, Filters: %d)\n",
            $name,
            $data['total_individual'],
            $data['actions'],
            $data['columns'],
            $data['filters']
        );
    }
    echo "\n";
}

echo "DETAILED BREAKDOWN BY RESOURCE\n";
echo "==============================\n\n";

foreach ($resources as $name => $data) {
    $statusIcon = $data['status'] === 'CONSOLIDATED' ? '✅' : '❌';
    
    echo "{$statusIcon} {$name}\n";
    echo str_repeat('-', strlen($name) + 3) . "\n";
    echo "File: {$data['file']}\n";
    echo "Status: {$data['status']}\n";
    echo "Individual Imports:\n";
    echo "  - Actions:  {$data['actions']}\n";
    echo "  - Columns:  {$data['columns']}\n";
    echo "  - Filters:  {$data['filters']}\n";
    echo "  - Total:    {$data['total_individual']}\n";
    echo "Consolidated Import: " . ($data['has_consolidated'] ? 'YES ✅' : 'NO ❌') . "\n";
    echo "Table Import: " . ($data['has_table_import'] ? 'YES' : 'NO') . "\n";
    
    if ($data['total_individual'] > 0) {
        $reduction = round((1 - (1 / ($data['total_individual'] + 1))) * 100, 1);
        echo "Potential Reduction: {$reduction}% ({$data['total_individual']} → 1 import)\n";
    }
    
    echo "\n";
}

// Priority recommendations
echo "================================================================================\n";
echo "RECOMMENDATIONS\n";
echo "================================================================================\n\n";

if (count($needsConsolidation) === 0) {
    echo "✅ All resources are already consolidated!\n";
    echo "   No further action needed.\n\n";
} else {
    echo "Priority Resources (5+ individual imports):\n";
    $highPriority = array_filter($needsConsolidation, fn($r) => $r['total_individual'] >= 5);
    
    if (count($highPriority) > 0) {
        foreach ($highPriority as $name => $data) {
            echo "  • {$name}: {$data['total_individual']} imports\n";
        }
    } else {
        echo "  None - all resources have < 5 individual imports\n";
    }
    
    echo "\n";
    echo "Estimated Total Effort:\n";
    echo "  • Resources to consolidate: " . count($needsConsolidation) . "\n";
    echo "  • Estimated time per resource: 30-60 minutes\n";
    echo "  • Total estimated time: " . (count($needsConsolidation) * 0.75) . " hours\n";
}

echo "\n";
echo "================================================================================\n";
echo "END OF REPORT\n";
echo "================================================================================\n";
