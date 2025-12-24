<?php

declare(strict_types=1);

/**
 * Translation Testing Script for Vilnius Utilities Platform
 * 
 * This script validates translation completeness across all supported locales
 * for the multi-tenant utilities billing platform. It checks critical UI strings
 * and provides detailed reporting on missing translations.
 * 
 * Usage:
 *   php test-translations.php [--locale=xx] [--verbose] [--json] [--missing-only]
 * 
 * Options:
 *   --locale=xx     Test only specific locale (en, lt, ru)
 *   --verbose       Show detailed output with translation values
 *   --json          Output results in JSON format
 *   --missing-only  Show only missing translations
 *   --help          Show this help message
 * 
 * @author Development Team
 * @version 1.0.0
 * @since 2024-12-24
 */

require_once 'vendor/autoload.php';

try {
    $app = require_once 'bootstrap/app.php';
} catch (Exception $e) {
    echo "❌ Failed to bootstrap Laravel application: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Configuration for translation testing
 */
class TranslationTester
{
    /**
     * Supported locales for the platform
     */
    private const SUPPORTED_LOCALES = ['en', 'lt', 'ru'];
    
    /**
     * Critical translation keys that must exist in all locales
     * Organized by functional area for better maintainability
     */
    private const CRITICAL_KEYS = [
        // Dashboard translations
        'dashboard.manager.title',
        'dashboard.manager.description',
        'dashboard.manager.stats.total_properties',
        'dashboard.manager.stats.active_meters',
        'dashboard.admin.title',
        'dashboard.tenant.title',
        
        // Landing page translations
        'landing.hero.title',
        'landing.hero.tagline',
        'landing.features_title',
        
        // Application branding
        'app.brand.name',
        'app.brand.product',
        
        // Common UI elements
        'common.yes',
        'common.no',
        'common.view',
        'common.edit',
        'common.delete',
        
        // Navigation
        'app.nav.dashboard',
        'app.nav.properties',
        'app.nav.meters',
        'app.nav.invoices',
        
        // Superadmin specific
        'superadmin.dashboard.title',
        'superadmin.navigation.tenants',
        
        // Error handling
        'app.errors.access_denied',
        'app.errors.generic',
    ];
    
    private array $options;
    private array $results = [];
    
    public function __construct(array $argv)
    {
        $this->parseArguments($argv);
    }
    
    /**
     * Parse command line arguments
     */
    private function parseArguments(array $argv): void
    {
        $this->options = [
            'locale' => null,
            'verbose' => false,
            'json' => false,
            'missing_only' => false,
            'help' => false,
        ];
        
        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--locale=')) {
                $this->options['locale'] = substr($arg, 9);
            } elseif ($arg === '--verbose') {
                $this->options['verbose'] = true;
            } elseif ($arg === '--json') {
                $this->options['json'] = true;
            } elseif ($arg === '--missing-only') {
                $this->options['missing_only'] = true;
            } elseif ($arg === '--help') {
                $this->options['help'] = true;
            }
        }
    }
    
    /**
     * Show help message
     */
    private function showHelp(): void
    {
        echo "Translation Testing Script for Vilnius Utilities Platform\n\n";
        echo "Usage: php test-translations.php [options]\n\n";
        echo "Options:\n";
        echo "  --locale=xx     Test only specific locale (en, lt, ru)\n";
        echo "  --verbose       Show detailed output with translation values\n";
        echo "  --json          Output results in JSON format\n";
        echo "  --missing-only  Show only missing translations\n";
        echo "  --help          Show this help message\n\n";
        echo "Examples:\n";
        echo "  php test-translations.php                    # Test all locales\n";
        echo "  php test-translations.php --locale=ru        # Test only Russian\n";
        echo "  php test-translations.php --missing-only     # Show only missing keys\n";
        echo "  php test-translations.php --json             # JSON output\n";
    }
    
    /**
     * Run the translation tests
     */
    public function run(): void
    {
        if ($this->options['help']) {
            $this->showHelp();
            return;
        }
        
        $locales = $this->options['locale'] 
            ? [$this->options['locale']] 
            : self::SUPPORTED_LOCALES;
        
        foreach ($locales as $locale) {
            if (!in_array($locale, self::SUPPORTED_LOCALES)) {
                echo "❌ Unsupported locale: {$locale}\n";
                continue;
            }
            
            $this->testLocale($locale);
        }
        
        if ($this->options['json']) {
            $this->outputJson();
        } else {
            $this->outputSummary();
        }
    }
    
    /**
     * Test translations for a specific locale
     */
    private function testLocale(string $locale): void
    {
        try {
            app()->setLocale($locale);
            
            if (!$this->options['json']) {
                echo "\n";
                echo "Testing {$this->getLocaleName($locale)} translations:\n";
                echo str_repeat('=', 50) . "\n";
            }
            
            $localeResults = [
                'locale' => $locale,
                'locale_name' => $this->getLocaleName($locale),
                'total_keys' => count(self::CRITICAL_KEYS),
                'missing_keys' => [],
                'present_keys' => [],
                'coverage_percentage' => 0,
            ];
            
            foreach (self::CRITICAL_KEYS as $key) {
                $translation = __($key);
                $isMissing = ($translation === $key);
                
                if ($isMissing) {
                    $localeResults['missing_keys'][] = $key;
                } else {
                    $localeResults['present_keys'][] = [
                        'key' => $key,
                        'value' => $translation,
                    ];
                }
                
                // Output individual results if not JSON mode
                if (!$this->options['json']) {
                    if (!$this->options['missing_only'] || $isMissing) {
                        $status = $isMissing ? '❌ MISSING' : '✅ OK';
                        $value = $this->options['verbose'] && !$isMissing ? " → {$translation}" : '';
                        echo sprintf("%-45s %s%s\n", $key, $status, $value);
                    }
                }
            }
            
            $localeResults['coverage_percentage'] = round(
                (count($localeResults['present_keys']) / $localeResults['total_keys']) * 100,
                2
            );
            
            $this->results[] = $localeResults;
            
        } catch (Exception $e) {
            echo "❌ Error testing locale {$locale}: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Get human-readable locale name
     */
    private function getLocaleName(string $locale): string
    {
        return match ($locale) {
            'en' => 'English',
            'lt' => 'Lithuanian (Lietuvių)',
            'ru' => 'Russian (Русский)',
            default => ucfirst($locale),
        };
    }
    
    /**
     * Output results in JSON format
     */
    private function outputJson(): void
    {
        $summary = [
            'timestamp' => date('c'),
            'total_locales_tested' => count($this->results),
            'total_keys_per_locale' => count(self::CRITICAL_KEYS),
            'results' => $this->results,
            'overall_status' => $this->getOverallStatus(),
        ];
        
        echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
    
    /**
     * Output summary statistics
     */
    private function outputSummary(): void
    {
        echo "\n";
        echo str_repeat('=', 60) . "\n";
        echo "TRANSLATION COVERAGE SUMMARY\n";
        echo str_repeat('=', 60) . "\n";
        
        $totalMissing = 0;
        $totalKeys = count(self::CRITICAL_KEYS) * count($this->results);
        
        foreach ($this->results as $result) {
            $missing = count($result['missing_keys']);
            $totalMissing += $missing;
            
            $status = $missing === 0 ? '✅' : ($missing <= 2 ? '⚠️' : '❌');
            
            echo sprintf(
                "%s %-20s %3d/%d keys (%5.1f%%) - %d missing\n",
                $status,
                $result['locale_name'],
                count($result['present_keys']),
                $result['total_keys'],
                $result['coverage_percentage'],
                $missing
            );
            
            // Show missing keys for this locale
            if ($missing > 0 && !$this->options['missing_only']) {
                foreach ($result['missing_keys'] as $missingKey) {
                    echo "    ❌ {$missingKey}\n";
                }
            }
        }
        
        echo str_repeat('-', 60) . "\n";
        
        $overallCoverage = $totalKeys > 0 ? round((($totalKeys - $totalMissing) / $totalKeys) * 100, 2) : 0;
        $overallStatus = $this->getOverallStatus();
        
        echo sprintf(
            "OVERALL: %s %d/%d keys (%5.1f%%) - %d missing across all locales\n",
            $overallStatus['icon'],
            $totalKeys - $totalMissing,
            $totalKeys,
            $overallCoverage,
            $totalMissing
        );
        
        echo "\nStatus: {$overallStatus['message']}\n";
        
        if ($totalMissing > 0) {
            echo "\n📝 Next steps:\n";
            echo "1. Add missing translations to respective lang/{locale}/*.php files\n";
            echo "2. Run tests again to verify completeness\n";
            echo "3. Update Filament resources to use translation keys\n";
        }
    }
    
    /**
     * Get overall status assessment
     */
    private function getOverallStatus(): array
    {
        $totalMissing = array_sum(array_column($this->results, 'missing_keys'));
        $totalMissing = array_sum(array_map('count', $totalMissing));
        
        if ($totalMissing === 0) {
            return [
                'status' => 'excellent',
                'icon' => '🎉',
                'message' => 'All critical translations are complete!'
            ];
        } elseif ($totalMissing <= 5) {
            return [
                'status' => 'good',
                'icon' => '⚠️',
                'message' => 'Minor translation gaps detected'
            ];
        } else {
            return [
                'status' => 'needs_work',
                'icon' => '❌',
                'message' => 'Significant translation work needed'
            ];
        }
    }
}

// Run the translation tester
try {
    $tester = new TranslationTester($argv);
    $tester->run();
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}