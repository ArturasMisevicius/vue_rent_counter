<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use App\Enums\UserRole;
use App\Models\Language;
use App\Models\User;
use App\Support\Localization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Language Switcher Component Tests
 * 
 * Tests the language switcher Blade components including:
 * - Component rendering with different locales
 * - Accessibility features
 * - Integration with locale system
 * - Security aspects
 * - User role-based visibility
 */
final class LanguageSwitcherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed languages for testing
        $this->seed(\Database\Seeders\LanguageSeeder::class);
        
        // Register the language switch route for testing
        Route::get('/language/{locale}', function () {
            return response('OK');
        })->name('language.switch');
    }

    /**
     * Test accessible language switcher component renders correctly
     */
    public function test_accessible_language_switcher_renders_correctly(): void
    {
        app()->setLocale('en');
        
        $availableLocales = Localization::availableLocales();
        
        $view = View::make('components.accessible-language-switcher', [
            'currentLocale' => 'en',
            'availableLocales' => $availableLocales,
        ]);
        
        $rendered = $view->render();
        
        // Should contain accessibility attributes
        expect($rendered)->toContain('aria-expanded');
        expect($rendered)->toContain('aria-haspopup');
        expect($rendered)->toContain('aria-labelledby');
        expect($rendered)->toContain('role="menu"');
        expect($rendered)->toContain('role="menuitem"');
        
        // Should contain screen reader text
        expect($rendered)->toContain('sr-only');
        expect($rendered)->toContain(__('common.current_language'));
        
        // Should contain all available languages
        expect($rendered)->toContain('English');
        expect($rendered)->toContain('Lithuanian');
        expect($rendered)->toContain('Russian');
        
        // Should contain language abbreviations
        expect($rendered)->toContain('EN');
        expect($rendered)->toContain('LT');
        expect($rendered)->toContain('RU');
    }

    /**
     * Test accessible language switcher with different current locales
     */
    public function test_accessible_language_switcher_with_different_current_locales(): void
    {
        $availableLocales = Localization::availableLocales();
        
        foreach (['lt', 'en', 'ru'] as $locale) {
            app()->setLocale($locale);
            
            $view = View::make('components.accessible-language-switcher', [
                'currentLocale' => $locale,
                'availableLocales' => $availableLocales,
            ]);
            
            $rendered = $view->render();
            
            // Current locale should be marked as current
            expect($rendered)->toContain('aria-current="true"');
            
            // Should contain check mark for current locale
            expect($rendered)->toContain('text-indigo-600');
            
            // Should show current locale in button
            $currentLocaleConfig = $availableLocales->firstWhere('code', $locale);
            expect($rendered)->toContain(__($currentLocaleConfig['label']));
        }
    }

    /**
     * Test language switcher links point to correct routes
     */
    public function test_language_switcher_links_point_to_correct_routes(): void
    {
        $availableLocales = Localization::availableLocales();
        
        $view = View::make('components.accessible-language-switcher', [
            'currentLocale' => 'en',
            'availableLocales' => $availableLocales,
        ]);
        
        $rendered = $view->render();
        
        // Should contain correct route URLs
        expect($rendered)->toContain(route('language.switch', 'lt'));
        expect($rendered)->toContain(route('language.switch', 'en'));
        expect($rendered)->toContain(route('language.switch', 'ru'));
    }

    /**
     * Test language switcher JavaScript functionality
     */
    public function test_language_switcher_javascript_functionality(): void
    {
        $availableLocales = Localization::availableLocales();
        
        $view = View::make('components.accessible-language-switcher', [
            'currentLocale' => 'en',
            'availableLocales' => $availableLocales,
        ]);
        
        $rendered = $view->render();
        
        // Should contain Alpine.js directives
        expect($rendered)->toContain('x-data');
        expect($rendered)->toContain('@click');
        expect($rendered)->toContain('@click.away');
        expect($rendered)->toContain('x-show');
        expect($rendered)->toContain('x-transition');
        
        // Should contain custom event dispatch
        expect($rendered)->toContain('$dispatch(\'language-changed\'');
        
        // Should contain screen reader announcement logic
        expect($rendered)->toContain('aria-live');
        expect($rendered)->toContain('aria-atomic');
    }

    /**
     * Test language switcher accessibility features
     */
    public function test_language_switcher_accessibility_features(): void
    {
        $availableLocales = Localization::availableLocales();
        
        $view = View::make('components.accessible-language-switcher', [
            'currentLocale' => 'en',
            'availableLocales' => $availableLocales,
        ]);
        
        $rendered = $view->render();
        
        // Should have proper ARIA attributes
        expect($rendered)->toContain('aria-label="' . __('common.language_switcher_label') . '"');
        expect($rendered)->toContain('aria-expanded="false"');
        expect($rendered)->toContain('aria-haspopup="true"');
        expect($rendered)->toContain(':aria-expanded="open"');
        
        // Should have proper role attributes
        expect($rendered)->toContain('role="menu"');
        expect($rendered)->toContain('role="menuitem"');
        expect($rendered)->toContain('role="none"');
        
        // Should have keyboard navigation support
        expect($rendered)->toContain('focus:outline-none');
        expect($rendered)->toContain('focus:ring-2');
        expect($rendered)->toContain('focus:bg-gray-100');
        
        // Should have screen reader support
        expect($rendered)->toContain('sr-only');
        expect($rendered)->toContain(__('common.current_language'));
    }

    /**
     * Test language switcher with missing translations
     */
    public function test_language_switcher_with_missing_translations(): void
    {
        // Temporarily remove a translation key
        $originalTranslations = trans('common');
        
        $availableLocales = collect([
            [
                'code' => 'en',
                'label' => 'missing.translation.key',
                'abbreviation' => 'EN',
            ],
        ]);
        
        $view = View::make('components.accessible-language-switcher', [
            'currentLocale' => 'en',
            'availableLocales' => $availableLocales,
        ]);
        
        $rendered = $view->render();
        
        // Should handle missing translations gracefully
        expect($rendered)->toContain('missing.translation.key');
    }

    /**
     * Test language switcher security - XSS prevention
     */
    public function test_language_switcher_xss_prevention(): void
    {
        // Create malicious locale data
        $maliciousLocales = collect([
            [
                'code' => '<script>alert("xss")</script>',
                'label' => '<img src=x onerror=alert("xss")>',
                'abbreviation' => '<svg onload=alert("xss")>',
            ],
        ]);
        
        $view = View::make('components.accessible-language-switcher', [
            'currentLocale' => 'en',
            'availableLocales' => $maliciousLocales,
        ]);
        
        $rendered = $view->render();
        
        // Should escape malicious content
        expect($rendered)->not->toContain('<script>');
        expect($rendered)->not->toContain('onerror=');
        expect($rendered)->not->toContain('onload=');
        
        // Should contain escaped versions
        expect($rendered)->toContain('&lt;script&gt;');
        expect($rendered)->toContain('&lt;img');
        expect($rendered)->toContain('&lt;svg');
    }

    /**
     * Test language switcher with empty locale list
     */
    public function test_language_switcher_with_empty_locale_list(): void
    {
        $view = View::make('components.accessible-language-switcher', [
            'currentLocale' => 'en',
            'availableLocales' => collect(),
        ]);
        
        $rendered = $view->render();
        
        // Should still render the component structure
        expect($rendered)->toContain('relative');
        expect($rendered)->toContain('x-data');
        
        // But should not contain any language options
        expect($rendered)->not->toContain('role="menuitem"');
    }

    /**
     * Test language switcher integration with NavigationComposer
     */
    public function test_language_switcher_integration_with_navigation_composer(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($user);
        
        // Register NavigationComposer
        View::composer('*', \App\View\Composers\NavigationComposer::class);
        
        // Create a test view that uses the composer data
        $testView = '
            @if($showTopLocaleSwitcher)
                @include("components.accessible-language-switcher", [
                    "currentLocale" => $currentLocale,
                    "availableLocales" => \App\Support\Localization::availableLocales()
                ])
            @endif
        ';
        
        View::addLocation(resource_path('views'));
        
        $rendered = view()->make('test-navigation', [], compact('testView'))->render();
        
        // Should include language switcher for admin user
        expect($rendered)->toContain('language-switcher');
        expect($rendered)->toContain('aria-label');
    }

    /**
     * Test language switcher with different user roles
     */
    public function test_language_switcher_with_different_user_roles(): void
    {
        // Register NavigationComposer
        View::composer('*', \App\View\Composers\NavigationComposer::class);
        
        $testView = '
            @if($showTopLocaleSwitcher ?? false)
                <div class="has-language-switcher">Language switcher visible</div>
            @else
                <div class="no-language-switcher">Language switcher hidden</div>
            @endif
        ';
        
        // Test with admin (should show switcher)
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($admin);
        
        $rendered = view()->make('test-role', [], compact('testView'))->render();
        expect($rendered)->toContain('has-language-switcher');
        
        // Test with manager (should NOT show switcher)
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $this->actingAs($manager);
        
        $rendered = view()->make('test-role', [], compact('testView'))->render();
        expect($rendered)->toContain('no-language-switcher');
        
        // Test with tenant (should NOT show switcher)
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $this->actingAs($tenant);
        
        $rendered = view()->make('test-role', [], compact('testView'))->render();
        expect($rendered)->toContain('no-language-switcher');
        
        // Test with superadmin (should NOT show switcher)
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $this->actingAs($superadmin);
        
        $rendered = view()->make('test-role', [], compact('testView'))->render();
        expect($rendered)->toContain('no-language-switcher');
    }

    /**
     * Test language switcher translation validation script
     */
    public function test_language_switcher_translation_validation_script(): void
    {
        $availableLocales = Localization::availableLocales();
        
        $view = View::make('components.accessible-language-switcher', [
            'currentLocale' => 'en',
            'availableLocales' => $availableLocales,
        ]);
        
        $rendered = $view->render();
        
        // Should contain translation validation script
        expect($rendered)->toContain('@push(\'scripts\')');
        expect($rendered)->toContain('requiredTranslations');
        expect($rendered)->toContain('common.language_switcher_label');
        expect($rendered)->toContain('common.current_language');
        expect($rendered)->toContain('common.language_changed_to');
        expect($rendered)->toContain('console.warn');
    }

    /**
     * Test language switcher responsive behavior
     */
    public function test_language_switcher_responsive_behavior(): void
    {
        $availableLocales = Localization::availableLocales();
        
        $view = View::make('components.accessible-language-switcher', [
            'currentLocale' => 'en',
            'availableLocales' => $availableLocales,
        ]);
        
        $rendered = $view->render();
        
        // Should contain responsive classes
        expect($rendered)->toContain('w-48'); // Fixed width for dropdown
        expect($rendered)->toContain('right-0'); // Right alignment
        expect($rendered)->toContain('mt-2'); // Margin top
        expect($rendered)->toContain('z-50'); // High z-index for overlay
        
        // Should contain proper positioning
        expect($rendered)->toContain('absolute');
        expect($rendered)->toContain('origin-top-right');
    }

    /**
     * Test language switcher performance with many locales
     */
    public function test_language_switcher_performance_with_many_locales(): void
    {
        // Create a large number of fake locales
        $manyLocales = collect();
        for ($i = 0; $i < 50; $i++) {
            $manyLocales->push([
                'code' => "lang{$i}",
                'label' => "Language {$i}",
                'abbreviation' => "L{$i}",
            ]);
        }
        
        $startTime = microtime(true);
        
        $view = View::make('components.accessible-language-switcher', [
            'currentLocale' => 'lang0',
            'availableLocales' => $manyLocales,
        ]);
        
        $rendered = $view->render();
        
        $endTime = microtime(true);
        $renderTime = $endTime - $startTime;
        
        // Should render within reasonable time
        expect($renderTime)->toBeLessThan(1.0, 'Language switcher should render quickly even with many locales');
        
        // Should contain all locales
        expect($rendered)->toContain('Language 0');
        expect($rendered)->toContain('Language 49');
        expect($rendered)->toContain('L0');
        expect($rendered)->toContain('L49');
    }

    /**
     * Test language switcher with custom styling
     */
    public function test_language_switcher_with_custom_styling(): void
    {
        $availableLocales = Localization::availableLocales();
        
        $view = View::make('components.accessible-language-switcher', [
            'currentLocale' => 'en',
            'availableLocales' => $availableLocales,
        ]);
        
        $rendered = $view->render();
        
        // Should contain Tailwind CSS classes
        expect($rendered)->toContain('inline-flex');
        expect($rendered)->toContain('items-center');
        expect($rendered)->toContain('px-3');
        expect($rendered)->toContain('py-2');
        expect($rendered)->toContain('border');
        expect($rendered)->toContain('border-gray-300');
        expect($rendered)->toContain('shadow-sm');
        expect($rendered)->toContain('text-sm');
        expect($rendered)->toContain('font-medium');
        expect($rendered)->toContain('rounded-md');
        expect($rendered)->toContain('text-gray-700');
        expect($rendered)->toContain('bg-white');
        expect($rendered)->toContain('hover:bg-gray-50');
        
        // Should contain focus styles
        expect($rendered)->toContain('focus:outline-none');
        expect($rendered)->toContain('focus:ring-2');
        expect($rendered)->toContain('focus:ring-offset-2');
        expect($rendered)->toContain('focus:ring-indigo-500');
        
        // Should contain dropdown styles
        expect($rendered)->toContain('rounded-md');
        expect($rendered)->toContain('shadow-lg');
        expect($rendered)->toContain('bg-white');
        expect($rendered)->toContain('ring-1');
        expect($rendered)->toContain('ring-black');
        expect($rendered)->toContain('ring-opacity-5');
    }
}