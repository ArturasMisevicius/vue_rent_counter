<?php

declare(strict_types=1);

/**
 * Simple Translation Testing Script
 * 
 * Framework-agnostic translation testing tool for Laravel projects.
 * Tests translation completeness and coverage without requiring Laravel bootstrap.
 * Ideal for CI/CD pipelines and standalone validation.
 * 
 * @author Vilnius Utilities Management Platform
 * @version 2.0.0
 * @since 1.0.0
 * 
 * Usage:
 *   php test-translations-simple.php        # Test all locales
 *   php test-translations-simple.php en     # Test English only
 *   php test-translations-simple.php lt     # Test Lithuanian only
 * 
 * Exit codes:
 *   0 - All tests passed
 *   1 - Translation errors found
 * 
 * Features:
 * - Framework-agnostic (no Laravel bootstrap required)
 * - Critical translation key validation
 * - Coverage reporting with detailed metrics
 * - Multi-locale support (en, lt)
 * - File structure validation
 * - CI/CD friendly output format
 */

// Configuration
$langPath = __DIR__ . '/lang';
$supportedLocales = ['en', 'lt'];

/**
 * Test translation completeness for a specific locale
 * 
 * Validates critical translation keys and provides detailed coverage metrics.
 * Tests both file accessibility and key resolution without Laravel dependencies.
 * 
 * @param string $locale The locale code to test (e.g., 'en', 'lt')
 * @return array{
 *     locale: string,
 *     missing: string[],
 *     present: array{key: string, value: string}[],
 *     files_checked: string[],
 *     coverage: float,
 *     errors: string[]
 * } Comprehensive test results
 * 
 * @throws InvalidArgumentException If locale is not supported
 */
function testTranslations(string $locale): array
{
    global $langPath;
    
    $results = [
        'locale' => $locale,
        'missing' => [],
        'present' => [],
        'files_checked' => [],
        'coverage' => 0.0,
        'errors' => []
    ];
    
    // Validate locale is supported
    global $supportedLocales;
    if (!in_array($locale, $supportedLocales, true)) {
        $results['errors'][] = "Unsupported locale: {$locale}. Supported: " . implode(', ', $supportedLocales);
        return $results;
    }
    
    // Critical translation keys for platform functionality
    // These keys are essential for the Vilnius Utilities Management Platform
    $criticalKeys = [
        // Brand and application identity
        'app.brand.name',           // Platform name
        'app.brand.product',        // Product identifier
        
        // Core navigation (Filament admin panel)
        'app.nav.dashboard',        // Main dashboard
        'app.nav.properties',       // Property management
        'app.nav.buildings',        // Building management
        'app.nav.meters',          // Meter management
        'app.nav.invoices',        // Invoice management
        'app.nav.tenants',         // Tenant management
        'app.nav.managers',        // Manager management
        'app.nav.users',           // User management
        'app.nav.settings',        // System settings
        'app.nav.reports',         // Reporting system
        'app.nav.audit',           // Audit logging
        'app.nav.logout',          // User logout
        
        // Error handling and user feedback
        'app.errors.access_denied', // Authorization errors
        'app.errors.generic',       // General error messages
        
        // Common UI elements (shared across platform)
        'common.created_at',        // Timestamp labels
        'common.updated_at',        // Timestamp labels
        'common.none',             // Empty state indicators
    ];
    
    // Load translation files (Laravel standard structure)
    $translationFiles = ['app', 'common', 'dashboard', 'invoice'];
    $translations = [];
    
    foreach ($translationFiles as $file) {
        $filePath = "{$langPath}/{$locale}/{$file}.php";
        if (file_exists($filePath)) {
            // Validate PHP syntax before including
            $syntaxCheck = shell_exec("php -l \"{$filePath}\" 2>&1");
            if (strpos($syntaxCheck, 'No syntax errors') === false) {
                $results['errors'][] = "Syntax error in {$file}.php: {$syntaxCheck}";
                continue;
            }
            
            $translations[$file] = include $filePath;
            $results['files_checked'][] = $file;
        } else {
            $results['errors'][] = "Missing translation file: {$file}.php";
        }
    }
    
    // Test each critical translation key
    foreach ($criticalKeys as $key) {
        $parts = explode('.', $key);
        $file = $parts[0];
        $keyPath = array_slice($parts, 1);
        
        $found = false;
        $value = null;
        
        if (isset($translations[$file])) {
            $current = $translations[$file];
            
            // Navigate through nested array structure
            foreach ($keyPath as $part) {
                if (is_array($current) && isset($current[$part])) {
                    $current = $current[$part];
                } else {
                    $current = null;
                    break;
                }
            }
            
            // Validate that we found a non-empty string value
            if ($current !== null && is_string($current) && trim($current) !== '') {
                $found = true;
                $value = $current;
            }
        }
        
        if ($found) {
            $results['present'][] = ['key' => $key, 'value' => $value];
        } else {
            $results['missing'][] = $key;
        }
    }
    
    // Calculate coverage percentage
    $totalKeys = count($criticalKeys);
    $presentKeys = count($results['present']);
    $results['coverage'] = $totalKeys > 0 ? ($presentKeys / $totalKeys) * 100 : 0.0;
    
    return $results;
}

/**
 * Display comprehensive test results in a user-friendly format
 * 
 * Outputs detailed translation test results including coverage metrics,
 * missing keys, and sample translations. Formats output for both human
 * readability and CI/CD pipeline consumption.
 * 
 * @param array{
 *     locale: string,
 *     missing: string[],
 *     present: array{key: string, value: string}[],
 *     files_checked: string[],
 *     coverage: float,
 *     errors: string[]
 * } $results Test results from testTranslations()
 * 
 * @return void
 */
function displayResults(array $results): void
{
    $locale = $results['locale'];
    $localeName = match($locale) {
        'en' => 'English',
        'lt' => 'Lithuanian', 
        'ru' => 'Russian',
        default => ucfirst($locale)
    };
    
    echo "\n=== {$localeName} ({$locale}) Translation Test ===\n";
    echo "Files checked: " . implode(', ', $results['files_checked']) . "\n";
    echo "Present: " . count($results['present']) . "\n";
    echo "Missing: " . count($results['missing']) . "\n";
    echo "Coverage: " . number_format($results['coverage'], 1) . "%\n";
    
    // Display any file or syntax errors
    if (!empty($results['errors'])) {
        echo "\n🚨 Errors Found:\n";
        foreach ($results['errors'] as $error) {
            echo "  ❌ {$error}\n";
        }
    }
    
    if (!empty($results['missing'])) {
        echo "\n❌ Missing translations:\n";
        foreach ($results['missing'] as $key) {
            echo "  • {$key}\n";
        }
    }
    
    if (!empty($results['present'])) {
        echo "\n✅ Present translations (sample):\n";
        $sample = array_slice($results['present'], 0, 5);
        foreach ($sample as $item) {
            $truncatedValue = strlen($item['value']) > 50 
                ? substr($item['value'], 0, 47) . '...' 
                : $item['value'];
            echo "  • {$item['key']} = '{$truncatedValue}'\n";
        }
        if (count($results['present']) > 5) {
            echo "  ... and " . (count($results['present']) - 5) . " more\n";
        }
    }
    
    // Coverage status indicator for CI/CD
    $coverageStatus = $results['coverage'] >= 95 ? '✅' : ($results['coverage'] >= 80 ? '⚠️' : '❌');
    echo "\n{$coverageStatus} Coverage: " . number_format($results['coverage'], 1) . "%";
    
    if ($results['coverage'] < 95) {
        echo " (Target: 95%+)";
    }
    echo "\n";
}

// Main execution logic
$exitCode = 0;
$overallResults = [];

// Handle command line arguments
$testLocale = $argv[1] ?? null;
$localesToTest = $testLocale ? [$testLocale] : $supportedLocales;

// Validate command line locale if provided
if ($testLocale && !in_array($testLocale, $supportedLocales, true)) {
    echo "❌ Error: Unsupported locale '{$testLocale}'\n";
    echo "Supported locales: " . implode(', ', $supportedLocales) . "\n";
    exit(1);
}

// Test specified locales
foreach ($localesToTest as $locale) {
    $results = testTranslations($locale);
    $overallResults[$locale] = $results;
    displayResults($results);
    
    // Set exit code if there are issues
    if (!empty($results['missing']) || !empty($results['errors']) || $results['coverage'] < 95) {
        $exitCode = 1;
    }
}

echo "\n=== Summary ===\n";

if ($exitCode === 0) {
    echo "✅ All translation tests passed successfully!\n";
    echo "All critical keys are present with 95%+ coverage.\n";
} else {
    echo "❌ Translation issues found:\n";
    
    foreach ($overallResults as $locale => $results) {
        if (!empty($results['missing']) || !empty($results['errors']) || $results['coverage'] < 95) {
            echo "  • {$locale}: ";
            $issues = [];
            if (!empty($results['missing'])) {
                $issues[] = count($results['missing']) . " missing keys";
            }
            if (!empty($results['errors'])) {
                $issues[] = count($results['errors']) . " errors";
            }
            if ($results['coverage'] < 95) {
                $issues[] = "coverage " . number_format($results['coverage'], 1) . "% (target: 95%+)";
            }
            echo implode(', ', $issues) . "\n";
        }
    }
    
    echo "\nNext steps:\n";
    echo "1. Add missing translation keys to respective lang/{locale}/*.php files\n";
    echo "2. Fix any syntax errors in translation files\n";
    echo "3. Re-run this script to verify fixes\n";
}

echo "\nFor detailed guidance, see: docs/development/translation-workflow.md\n";

exit($exitCode);