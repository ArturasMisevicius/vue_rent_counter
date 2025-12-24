<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

/**
 * Landing Page Localization Tests
 * 
 * Tests the landing page translation system to ensure all keys exist
 * in both English and Lithuanian locales and display correctly.
 */
final class LandingPageLocalizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that all required landing translation keys exist in English
     */
    public function test_has_all_required_english_landing_translation_keys(): void
    {
        app()->setLocale('en');
        
        $requiredKeys = [
            // CTA Bar
            'landing.cta_bar.eyebrow',
            'landing.cta_bar.title',
            
            // Hero Section
            'landing.hero.badge',
            'landing.hero.title',
            'landing.hero.tagline',
            
            // Dashboard
            'landing.dashboard.draft_invoices',
            'landing.dashboard.draft_invoices_hint',
            'landing.dashboard.electricity',
            'landing.dashboard.electricity_status',
            'landing.dashboard.healthy',
            'landing.dashboard.heating',
            'landing.dashboard.heating_status',
            'landing.dashboard.live_overview',
            'landing.dashboard.meters_validated',
            'landing.dashboard.meters_validated_hint',
            'landing.dashboard.portfolio_health',
            'landing.dashboard.recent_readings',
            'landing.dashboard.trusted',
            'landing.dashboard.water',
            'landing.dashboard.water_status',
            
            // Features
            'landing.features_title',
            'landing.features_subtitle',
            'landing.features.unified_metering.title',
            'landing.features.unified_metering.description',
            'landing.features.accurate_invoicing.title',
            'landing.features.accurate_invoicing.description',
            'landing.features.role_access.title',
            'landing.features.role_access.description',
            'landing.features.reporting.title',
            'landing.features.reporting.description',
            'landing.features.performance.title',
            'landing.features.performance.description',
            'landing.features.tenant_clarity.title',
            'landing.features.tenant_clarity.description',
            
            // FAQ
            'landing.faq_intro',
            'landing.faq_section.eyebrow',
            'landing.faq_section.title',
            'landing.faq_section.category_prefix',
            'landing.faq.validation.question',
            'landing.faq.validation.answer',
            'landing.faq.tenants.question',
            'landing.faq.tenants.answer',
            'landing.faq.invoices.question',
            'landing.faq.invoices.answer',
            'landing.faq.security.question',
            'landing.faq.security.answer',
            'landing.faq.support.question',
            'landing.faq.support.answer',
            
            // Metrics
            'landing.metrics.cache',
            'landing.metrics.isolation',
            'landing.metrics.readings',
            'landing.metric_values.five_minutes',
            'landing.metric_values.full',
            'landing.metric_values.zero',
        ];
        
        foreach ($requiredKeys as $key) {
            $translation = __($key);
            $this->assertNotEquals($key, $translation, "Missing English translation for key: {$key}");
            $this->assertNotEmpty($translation, "Empty English translation for key: {$key}");
        }
    }

    /**
     * Test that all required landing translation keys exist in Lithuanian
     */
    public function test_has_all_required_lithuanian_landing_translation_keys(): void
    {
        app()->setLocale('lt');
        
        $requiredKeys = [
            // CTA Bar
            'landing.cta_bar.eyebrow',
            'landing.cta_bar.title',
            
            // Hero Section
            'landing.hero.badge',
            'landing.hero.title',
            'landing.hero.tagline',
            
            // Dashboard
            'landing.dashboard.draft_invoices',
            'landing.dashboard.draft_invoices_hint',
            'landing.dashboard.electricity',
            'landing.dashboard.electricity_status',
            'landing.dashboard.healthy',
            'landing.dashboard.heating',
            'landing.dashboard.heating_status',
            'landing.dashboard.live_overview',
            'landing.dashboard.meters_validated',
            'landing.dashboard.meters_validated_hint',
            'landing.dashboard.portfolio_health',
            'landing.dashboard.recent_readings',
            'landing.dashboard.trusted',
            'landing.dashboard.water',
            'landing.dashboard.water_status',
            
            // Features
            'landing.features_title',
            'landing.features_subtitle',
            'landing.features.unified_metering.title',
            'landing.features.unified_metering.description',
            'landing.features.accurate_invoicing.title',
            'landing.features.accurate_invoicing.description',
            'landing.features.role_access.title',
            'landing.features.role_access.description',
            'landing.features.reporting.title',
            'landing.features.reporting.description',
            'landing.features.performance.title',
            'landing.features.performance.description',
            'landing.features.tenant_clarity.title',
            'landing.features.tenant_clarity.description',
            
            // FAQ
            'landing.faq_intro',
            'landing.faq_section.eyebrow',
            'landing.faq_section.title',
            'landing.faq_section.category_prefix',
            'landing.faq.validation.question',
            'landing.faq.validation.answer',
            'landing.faq.tenants.question',
            'landing.faq.tenants.answer',
            'landing.faq.invoices.question',
            'landing.faq.invoices.answer',
            'landing.faq.security.question',
            'landing.faq.security.answer',
            'landing.faq.support.question',
            'landing.faq.support.answer',
            
            // Metrics
            'landing.metrics.cache',
            'landing.metrics.isolation',
            'landing.metrics.readings',
            'landing.metric_values.five_minutes',
            'landing.metric_values.full',
            'landing.metric_values.zero',
        ];
        
        foreach ($requiredKeys as $key) {
            $translation = __($key);
            $this->assertNotEquals($key, $translation, "Missing Lithuanian translation for key: {$key}");
            $this->assertNotEmpty($translation, "Empty Lithuanian translation for key: {$key}");
        }
    }

    /**
     * Test that English and Lithuanian have the same translation key structure
     */
    public function test_english_and_lithuanian_have_same_key_structure(): void
    {
        $englishKeys = collect(\Illuminate\Support\Arr::dot(Lang::get('landing', [], 'en')));
        $lithuanianKeys = collect(\Illuminate\Support\Arr::dot(Lang::get('landing', [], 'lt')));
        
        $this->assertEquals(
            $englishKeys->keys()->sort()->values(),
            $lithuanianKeys->keys()->sort()->values(),
            'English and Lithuanian translation keys do not match'
        );
    }

    /**
     * Test that landing page displays content in English
     */
    public function test_landing_page_displays_english_content(): void
    {
        app()->setLocale('en');
        
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee(__('landing.hero.title'));
        $response->assertSee(__('landing.hero.tagline'));
        $response->assertSee(__('landing.dashboard.draft_invoices'));
        $response->assertSee(__('landing.features_title'));
    }

    /**
     * Test that landing page displays content in Lithuanian
     */
    public function test_landing_page_displays_lithuanian_content(): void
    {
        session(['locale' => 'lt']);
        app()->setLocale('lt');
        
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee(__('landing.hero.title'));
        $response->assertSee(__('landing.hero.tagline'));
        $response->assertSee(__('landing.dashboard.draft_invoices'));
        $response->assertSee(__('landing.features_title'));
    }

    /**
     * Test that no translation keys are displayed (indicating missing translations)
     */
    public function test_no_translation_keys_displayed_on_landing_page(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        // Check that no translation keys are displayed
        $response->assertDontSee('landing.hero.title');
        $response->assertDontSee('landing.hero.tagline');
        $response->assertDontSee('landing.dashboard.draft_invoices');
        $response->assertDontSee('landing.features_title');
    }

    /**
     * Test that specific utilities-related content is present
     */
    public function test_utilities_specific_content_is_present(): void
    {
        app()->setLocale('en');
        
        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        // Check for utilities-specific terminology
        $response->assertSee('Utilities Management');
        $response->assertSee('Vilnius Utilities Platform');
        $response->assertSee('Meter Readings');
        $response->assertSee('Invoice');
        $response->assertSee('Electricity');
        $response->assertSee('Water');
        $response->assertSee('Heating');
    }

    /**
     * Test that Lithuanian utilities content is present
     */
    public function test_lithuanian_utilities_content_is_present(): void
    {
        session(['locale' => 'lt']);
        app()->setLocale('lt');
        
        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        // Check for Lithuanian utilities-specific terminology
        $response->assertSee('Komunalinių paslaugų valdymas');
        $response->assertSee('Vilniaus komunalinių paslaugų platforma');
        $response->assertSee('Skaitiklių rodmenys');
        $response->assertSee('Elektra');
        $response->assertSee('Vanduo');
        $response->assertSee('Šildymas');
    }

    /**
     * Test that FAQ content is properly structured
     */
    public function test_faq_content_structure(): void
    {
        app()->setLocale('en');
        
        // Test that FAQ questions and answers exist and are not empty
        $faqItems = ['validation', 'tenants', 'invoices', 'security', 'support'];
        
        foreach ($faqItems as $item) {
            $question = __("landing.faq.{$item}.question");
            $answer = __("landing.faq.{$item}.answer");
            
            $this->assertNotEquals("landing.faq.{$item}.question", $question);
            $this->assertNotEquals("landing.faq.{$item}.answer", $answer);
            $this->assertNotEmpty($question);
            $this->assertNotEmpty($answer);
            
            // Ensure questions end with question mark
            $this->assertStringEndsWith('?', $question, "FAQ question should end with question mark: {$item}");
            
            // Ensure answers are substantial (more than 20 characters)
            $this->assertGreaterThan(20, strlen($answer), "FAQ answer should be substantial: {$item}");
        }
    }

    /**
     * Test that feature descriptions are comprehensive
     */
    public function test_feature_descriptions_are_comprehensive(): void
    {
        app()->setLocale('en');
        
        $features = ['unified_metering', 'accurate_invoicing', 'role_access', 'reporting', 'performance', 'tenant_clarity'];
        
        foreach ($features as $feature) {
            $title = __("landing.features.{$feature}.title");
            $description = __("landing.features.{$feature}.description");
            
            $this->assertNotEquals("landing.features.{$feature}.title", $title);
            $this->assertNotEquals("landing.features.{$feature}.description", $description);
            $this->assertNotEmpty($title);
            $this->assertNotEmpty($description);
            
            // Ensure descriptions are substantial (more than 30 characters)
            $this->assertGreaterThan(30, strlen($description), "Feature description should be substantial: {$feature}");
        }
    }

    /**
     * Test that metric values are properly formatted
     */
    public function test_metric_values_are_properly_formatted(): void
    {
        app()->setLocale('en');
        
        $fiveMinutes = __('landing.metric_values.five_minutes');
        $full = __('landing.metric_values.full');
        $zero = __('landing.metric_values.zero');
        
        // Test specific formatting expectations
        $this->assertStringContains('5', $fiveMinutes);
        $this->assertStringContains('minute', $fiveMinutes);
        $this->assertEquals('100%', $full);
        $this->assertEquals('0', $zero);
    }
}