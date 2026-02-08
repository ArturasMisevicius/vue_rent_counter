<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

/**
 * Translation Completeness Tests
 * 
 * Validates that all critical translation keys exist across all supported locales
 * for the Vilnius Utilities Platform. Ensures UI consistency and prevents
 * missing translation keys from appearing in the interface.
 * 
 * @covers Translation system completeness
 * @group translations
 * @group localization
 */
final class TranslationCompletenessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Supported locales for the platform
     */
    private const SUPPORTED_LOCALES = ['en', 'lt', 'ru'];
    
    /**
     * Critical translation keys that must exist in all locales
     * These keys are essential for core platform functionality
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

    /**
     * Test that all critical translation keys exist in all supported locales
     */
    public function test_all_critical_keys_exist_in_all_locales(): void
    {
        $missingTranslations = [];
        
        foreach (self::SUPPORTED_LOCALES as $locale) {
            app()->setLocale($locale);
            
            foreach (self::CRITICAL_KEYS as $key) {
                $translation = __($key);
                
                if ($translation === $key) {
                    $missingTranslations[$locale][] = $key;
                }
            }
        }
        
        if (!empty($missingTranslations)) {
            $errorMessage = "Missing translations found:\n";
            foreach ($missingTranslations as $locale => $keys) {
                $errorMessage .= "  {$locale}: " . implode(', ', $keys) . "\n";
            }
            
            $this->fail($errorMessage);
        }
        
        $this->assertTrue(true, 'All critical translation keys exist in all locales');
    }

    /**
     * Test that English translations exist (base locale)
     */
    public function test_english_translations_exist(): void
    {
        app()->setLocale('en');
        
        foreach (self::CRITICAL_KEYS as $key) {
            $translation = __($key);
            $this->assertNotEquals($key, $translation, "Missing English translation for key: {$key}");
            $this->assertNotEmpty($translation, "Empty English translation for key: {$key}");
        }
    }

    /**
     * Test that Lithuanian translations exist
     */
    public function test_lithuanian_translations_exist(): void
    {
        app()->setLocale('lt');
        
        foreach (self::CRITICAL_KEYS as $key) {
            $translation = __($key);
            $this->assertNotEquals($key, $translation, "Missing Lithuanian translation for key: {$key}");
            $this->assertNotEmpty($translation, "Empty Lithuanian translation for key: {$key}");
        }
    }

    /**
     * Test that Russian translations exist
     */
    public function test_russian_translations_exist(): void
    {
        app()->setLocale('ru');
        
        foreach (self::CRITICAL_KEYS as $key) {
            $translation = __($key);
            $this->assertNotEquals($key, $translation, "Missing Russian translation for key: {$key}");
            $this->assertNotEmpty($translation, "Empty Russian translation for key: {$key}");
        }
    }

    /**
     * Test that all locales have the same translation key structure
     */
    public function test_all_locales_have_same_key_structure(): void
    {
        $baseKeys = collect(\Illuminate\Support\Arr::dot(Lang::get('dashboard', [], 'en')));
        
        foreach (['lt', 'ru'] as $locale) {
            $localeKeys = collect(\Illuminate\Support\Arr::dot(Lang::get('dashboard', [], $locale)));
            
            $this->assertEquals(
                $baseKeys->keys()->sort()->values(),
                $localeKeys->keys()->sort()->values(),
                "Translation key structure mismatch between English and {$locale}"
            );
        }
    }

    /**
     * Test that dashboard translations are complete
     */
    public function test_dashboard_translations_are_complete(): void
    {
        $dashboardKeys = [
            'dashboard.manager.title',
            'dashboard.manager.description',
            'dashboard.admin.title',
            'dashboard.tenant.title',
        ];
        
        foreach (self::SUPPORTED_LOCALES as $locale) {
            app()->setLocale($locale);
            
            foreach ($dashboardKeys as $key) {
                $translation = __($key);
                $this->assertNotEquals($key, $translation, "Missing dashboard translation for {$key} in {$locale}");
            }
        }
    }

    /**
     * Test that superadmin translations are complete
     */
    public function test_superadmin_translations_are_complete(): void
    {
        $superadminKeys = [
            'superadmin.dashboard.title',
            'superadmin.dashboard.subtitle',
            'superadmin.navigation.tenants',
            'superadmin.navigation.audit_logs',
        ];
        
        foreach (self::SUPPORTED_LOCALES as $locale) {
            app()->setLocale($locale);
            
            foreach ($superadminKeys as $key) {
                $translation = __($key);
                $this->assertNotEquals($key, $translation, "Missing superadmin translation for {$key} in {$locale}");
            }
        }
    }

    /**
     * Test that common UI translations are complete
     */
    public function test_common_ui_translations_are_complete(): void
    {
        $commonKeys = [
            'common.yes',
            'common.no',
            'common.view',
            'common.edit',
            'common.delete',
            'common.language',
        ];
        
        foreach (self::SUPPORTED_LOCALES as $locale) {
            app()->setLocale($locale);
            
            foreach ($commonKeys as $key) {
                $translation = __($key);
                $this->assertNotEquals($key, $translation, "Missing common translation for {$key} in {$locale}");
            }
        }
    }

    /**
     * Test that application branding translations exist
     */
    public function test_application_branding_translations_exist(): void
    {
        $brandingKeys = [
            'app.brand.name',
            'app.brand.product',
        ];
        
        foreach (self::SUPPORTED_LOCALES as $locale) {
            app()->setLocale($locale);
            
            foreach ($brandingKeys as $key) {
                $translation = __($key);
                $this->assertNotEquals($key, $translation, "Missing branding translation for {$key} in {$locale}");
                $this->assertNotEmpty($translation, "Empty branding translation for {$key} in {$locale}");
            }
        }
    }

    /**
     * Test that navigation translations are complete
     */
    public function test_navigation_translations_are_complete(): void
    {
        $navKeys = [
            'app.nav.dashboard',
            'app.nav.properties',
            'app.nav.meters',
            'app.nav.invoices',
            'app.nav.tenants',
            'app.nav.users',
        ];
        
        foreach (self::SUPPORTED_LOCALES as $locale) {
            app()->setLocale($locale);
            
            foreach ($navKeys as $key) {
                $translation = __($key);
                $this->assertNotEquals($key, $translation, "Missing navigation translation for {$key} in {$locale}");
            }
        }
    }

    /**
     * Test that error message translations are complete
     */
    public function test_error_message_translations_are_complete(): void
    {
        $errorKeys = [
            'app.errors.access_denied',
            'app.errors.generic',
            'app.errors.forbidden_action',
        ];
        
        foreach (self::SUPPORTED_LOCALES as $locale) {
            app()->setLocale($locale);
            
            foreach ($errorKeys as $key) {
                $translation = __($key);
                $this->assertNotEquals($key, $translation, "Missing error translation for {$key} in {$locale}");
            }
        }
    }

    /**
     * Test that no placeholder translations remain in production files
     */
    public function test_no_placeholder_translations_remain(): void
    {
        $placeholderPatterns = [
            'Title',
            'Description',
            'Label',
            'Placeholder',
            'TODO',
            'FIXME',
        ];
        
        foreach (self::SUPPORTED_LOCALES as $locale) {
            app()->setLocale($locale);
            
            foreach (self::CRITICAL_KEYS as $key) {
                $translation = __($key);
                
                foreach ($placeholderPatterns as $pattern) {
                    $this->assertNotEquals(
                        $pattern,
                        $translation,
                        "Placeholder translation '{$pattern}' found for key {$key} in {$locale}"
                    );
                }
            }
        }
    }

    /**
     * Test translation coverage percentage
     */
    public function test_translation_coverage_meets_minimum_threshold(): void
    {
        $minimumCoverage = 95.0; // 95% minimum coverage required
        
        foreach (self::SUPPORTED_LOCALES as $locale) {
            app()->setLocale($locale);
            
            $totalKeys = count(self::CRITICAL_KEYS);
            $missingKeys = 0;
            
            foreach (self::CRITICAL_KEYS as $key) {
                $translation = __($key);
                if ($translation === $key) {
                    $missingKeys++;
                }
            }
            
            $coverage = (($totalKeys - $missingKeys) / $totalKeys) * 100;
            
            $this->assertGreaterThanOrEqual(
                $minimumCoverage,
                $coverage,
                "Translation coverage for {$locale} is {$coverage}%, below minimum {$minimumCoverage}%"
            );
        }
    }
}