<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use App\Http\Middleware\SetLocale;
use App\Models\Language;
use App\Models\User;
use App\Support\Localization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * Comprehensive Locale System Tests
 * 
 * Tests the complete localization system including:
 * - Configuration and setup
 * - Language model functionality
 * - Middleware behavior
 * - Controller actions
 * - Session persistence
 * - User preferences
 * - Fallback behavior
 * - Cache functionality
 * - Security aspects
 */
final class LocaleSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed languages for testing
        $this->seed(\Database\Seeders\LanguageSeeder::class);
    }

    /**
     * Test locale configuration is properly set up
     */
    public function test_locale_configuration_is_properly_set(): void
    {
        // Test default locale
        expect(config('locales.default'))->toBe('lt');
        
        // Test fallback locale
        expect(config('locales.fallback'))->toBe('en');
        
        // Test available locales structure
        $available = config('locales.available');
        expect($available)->toBeArray();
        expect($available)->toHaveKeys(['lt', 'en', 'ru']);
        
        // Test each locale has required structure
        foreach ($available as $code => $config) {
            expect($config)->toHaveKeys(['label', 'abbreviation']);
            expect($config['label'])->toBeString();
            expect($config['abbreviation'])->toBeString();
        }
    }

    /**
     * Test Language model basic functionality
     */
    public function test_language_model_basic_functionality(): void
    {
        // Test languages were seeded
        expect(Language::count())->toBe(3);
        
        // Test Lithuanian is default
        $defaultLanguage = Language::getDefault();
        expect($defaultLanguage)->not->toBeNull();
        expect($defaultLanguage->code)->toBe('lt');
        expect($defaultLanguage->is_default)->toBeTrue();
        
        // Test all languages are active
        $activeLanguages = Language::getActiveLanguages();
        expect($activeLanguages)->toHaveCount(3);
        
        // Test languages are ordered by display_order
        $codes = $activeLanguages->pluck('code')->toArray();
        expect($codes)->toBe(['lt', 'en', 'ru']);
    }

    /**
     * Test Language model caching functionality
     */
    public function test_language_model_caching(): void
    {
        // Clear cache first
        Cache::forget('languages.active');
        Cache::forget('languages.default');
        
        // First call should hit database
        $activeLanguages1 = Language::getActiveLanguages();
        expect(Cache::has('languages.active'))->toBeTrue();
        
        $defaultLanguage1 = Language::getDefault();
        expect(Cache::has('languages.default'))->toBeTrue();
        
        // Second call should use cache
        $activeLanguages2 = Language::getActiveLanguages();
        $defaultLanguage2 = Language::getDefault();
        
        expect($activeLanguages1)->toEqual($activeLanguages2);
        expect($defaultLanguage1)->toEqual($defaultLanguage2);
    }

    /**
     * Test Language model cache invalidation
     */
    public function test_language_model_cache_invalidation(): void
    {
        // Populate cache
        Language::getActiveLanguages();
        Language::getDefault();
        
        expect(Cache::has('languages.active'))->toBeTrue();
        expect(Cache::has('languages.default'))->toBeTrue();
        
        // Update a language
        $language = Language::first();
        $language->update(['name' => 'Updated Name']);
        
        // Cache should be cleared
        expect(Cache::has('languages.active'))->toBeFalse();
        expect(Cache::has('languages.default'))->toBeFalse();
    }

    /**
     * Test Localization support class
     */
    public function test_localization_support_class(): void
    {
        // Test availableLocales method
        $locales = Localization::availableLocales();
        expect($locales)->toHaveCount(3);
        
        $firstLocale = $locales->first();
        expect($firstLocale)->toHaveKeys(['code', 'label', 'abbreviation']);
        
        // Test isAvailable method
        expect(Localization::isAvailable('lt'))->toBeTrue();
        expect(Localization::isAvailable('en'))->toBeTrue();
        expect(Localization::isAvailable('ru'))->toBeTrue();
        expect(Localization::isAvailable('fr'))->toBeFalse();
        expect(Localization::isAvailable('invalid'))->toBeFalse();
        
        // Test fallbackLocale method
        expect(Localization::fallbackLocale())->toBe('en');
        
        // Test currentLocale method
        app()->setLocale('lt');
        expect(Localization::currentLocale())->toBe('lt');
        
        app()->setLocale('en');
        expect(Localization::currentLocale())->toBe('en');
        
        // Test getLocaleConfig method
        $ltConfig = Localization::getLocaleConfig('lt');
        expect($ltConfig)->toBeArray();
        expect($ltConfig)->toHaveKeys(['label', 'abbreviation']);
        
        $invalidConfig = Localization::getLocaleConfig('invalid');
        expect($invalidConfig)->toBeNull();
    }

    /**
     * Test SetLocale middleware with session locale
     */
    public function test_set_locale_middleware_with_session(): void
    {
        $request = Request::create('/test');
        $request->setLaravelSession(session());
        
        // Set locale in session
        session(['locale' => 'ru']);
        
        $middleware = new SetLocale();
        
        $middleware->handle($request, function ($req) {
            expect(app()->getLocale())->toBe('ru');
            return response('OK');
        });
    }

    /**
     * Test SetLocale middleware with user preference
     */
    public function test_set_locale_middleware_with_user_preference(): void
    {
        $user = User::factory()->create(['locale' => 'en']);
        
        $request = Request::create('/test');
        $request->setLaravelSession(session());
        $request->setUserResolver(fn () => $user);
        
        $middleware = new SetLocale();
        
        $middleware->handle($request, function ($req) {
            expect(app()->getLocale())->toBe('en');
            return response('OK');
        });
    }

    /**
     * Test SetLocale middleware with Accept-Language header
     */
    public function test_set_locale_middleware_with_accept_language(): void
    {
        $request = Request::create('/test');
        $request->setLaravelSession(session());
        $request->headers->set('Accept-Language', 'ru,en;q=0.9,lt;q=0.8');
        
        $middleware = new SetLocale();
        
        $middleware->handle($request, function ($req) {
            expect(app()->getLocale())->toBe('ru');
            return response('OK');
        });
    }

    /**
     * Test SetLocale middleware fallback behavior
     */
    public function test_set_locale_middleware_fallback(): void
    {
        $request = Request::create('/test');
        $request->setLaravelSession(session());
        $request->headers->set('Accept-Language', 'fr,de;q=0.9');
        
        $middleware = new SetLocale();
        
        $middleware->handle($request, function ($req) {
            expect(app()->getLocale())->toBe('en'); // fallback
            return response('OK');
        });
    }

    /**
     * Test SetLocale middleware with invalid session locale
     */
    public function test_set_locale_middleware_with_invalid_session_locale(): void
    {
        $request = Request::create('/test');
        $request->setLaravelSession(session());
        
        // Set invalid locale in session
        session(['locale' => 'invalid']);
        
        $middleware = new SetLocale();
        
        $middleware->handle($request, function ($req) {
            expect(app()->getLocale())->toBe('en'); // should fallback
            return response('OK');
        });
    }

    /**
     * Test language switching controller - valid locale
     */
    public function test_language_controller_switch_valid_locale(): void
    {
        $response = $this->get('/language/ru');
        
        $response->assertRedirect();
        $response->assertSessionHas('locale', 'ru');
        $response->assertSessionHas('success');
        
        // Test locale is set for current request
        expect(app()->getLocale())->toBe('ru');
    }

    /**
     * Test language switching controller - invalid locale
     */
    public function test_language_controller_switch_invalid_locale(): void
    {
        $response = $this->get('/language/invalid');
        
        $response->assertNotFound();
    }

    /**
     * Test language switching controller with authenticated user
     */
    public function test_language_controller_switch_with_authenticated_user(): void
    {
        $user = User::factory()->create(['locale' => 'en']);
        
        $response = $this->actingAs($user)->get('/language/lt');
        
        $response->assertRedirect();
        $response->assertSessionHas('locale', 'lt');
        
        // User preference should be updated
        expect($user->fresh()->locale)->toBe('lt');
    }

    /**
     * Test language switching controller redirect behavior
     */
    public function test_language_controller_redirect_behavior(): void
    {
        // Test redirect back to previous page
        $response = $this->from('/dashboard')->get('/language/en');
        
        $response->assertRedirect('/dashboard');
    }

    /**
     * Test session persistence across requests
     */
    public function test_session_persistence_across_requests(): void
    {
        // First request - set locale
        $this->get('/language/ru');
        
        // Second request - locale should persist
        $response = $this->get('/');
        
        expect(session('locale'))->toBe('ru');
        expect(app()->getLocale())->toBe('ru');
    }

    /**
     * Test locale switching with middleware integration
     */
    public function test_locale_switching_with_middleware_integration(): void
    {
        // Set initial locale
        $this->get('/language/lt');
        expect(app()->getLocale())->toBe('lt');
        
        // Make another request - middleware should set locale from session
        $this->get('/');
        expect(app()->getLocale())->toBe('lt');
        
        // Switch locale
        $this->get('/language/en');
        expect(app()->getLocale())->toBe('en');
        
        // Make another request - should use new locale
        $this->get('/');
        expect(app()->getLocale())->toBe('en');
    }

    /**
     * Test translation key resolution in different locales
     */
    public function test_translation_key_resolution(): void
    {
        $testKeys = [
            'common.language',
            'common.english',
            'common.lithuanian',
            'common.russian',
            'common.language_switched',
        ];
        
        foreach (['lt', 'en', 'ru'] as $locale) {
            app()->setLocale($locale);
            
            foreach ($testKeys as $key) {
                $translation = __($key);
                expect($translation)->not->toBe($key, "Missing translation for '{$key}' in locale '{$locale}'");
                expect($translation)->not->toBeEmpty("Empty translation for '{$key}' in locale '{$locale}'");
            }
        }
    }

    /**
     * Test fallback translation behavior
     */
    public function test_fallback_translation_behavior(): void
    {
        // Set locale to Lithuanian
        app()->setLocale('lt');
        
        // Test existing key
        expect(__('common.language'))->not->toBe('common.language');
        
        // Test non-existent key should fallback to English
        $nonExistentKey = 'non.existent.key.for.testing';
        $translation = __($nonExistentKey);
        
        // Should return the key itself if not found in any locale
        expect($translation)->toBe($nonExistentKey);
    }

    /**
     * Test locale-specific number and date formatting
     */
    public function test_locale_specific_formatting(): void
    {
        // Test Lithuanian locale
        app()->setLocale('lt');
        $ltDate = now()->translatedFormat('F j, Y');
        expect($ltDate)->toBeString();
        
        // Test English locale
        app()->setLocale('en');
        $enDate = now()->translatedFormat('F j, Y');
        expect($enDate)->toBeString();
        
        // Dates should be different due to translation
        expect($ltDate)->not->toBe($enDate);
    }

    /**
     * Test security aspects of locale switching
     */
    public function test_locale_switching_security(): void
    {
        // Test XSS prevention in locale parameter
        $response = $this->get('/language/<script>alert("xss")</script>');
        $response->assertNotFound();
        
        // Test path traversal prevention
        $response = $this->get('/language/../../../etc/passwd');
        $response->assertNotFound();
        
        // Test SQL injection prevention
        $response = $this->get('/language/\'; DROP TABLE users; --');
        $response->assertNotFound();
        
        // Test only valid locales are accepted
        foreach (['lt', 'en', 'ru'] as $validLocale) {
            $response = $this->get("/language/{$validLocale}");
            $response->assertRedirect(); // Should succeed
        }
        
        foreach (['fr', 'de', 'es', 'invalid'] as $invalidLocale) {
            $response = $this->get("/language/{$invalidLocale}");
            $response->assertNotFound(); // Should fail
        }
    }

    /**
     * Test locale switching performance
     */
    public function test_locale_switching_performance(): void
    {
        // Warm up cache
        Language::getActiveLanguages();
        
        $startTime = microtime(true);
        
        // Perform multiple locale switches
        for ($i = 0; $i < 10; $i++) {
            $locale = ['lt', 'en', 'ru'][$i % 3];
            $this->get("/language/{$locale}");
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // Should complete within reasonable time (adjust threshold as needed)
        expect($duration)->toBeLessThan(2.0, 'Locale switching should be performant');
    }

    /**
     * Test concurrent locale switching
     */
    public function test_concurrent_locale_switching(): void
    {
        // Simulate concurrent requests with different locales
        $responses = [];
        
        foreach (['lt', 'en', 'ru'] as $locale) {
            $responses[] = $this->get("/language/{$locale}");
        }
        
        // All requests should succeed
        foreach ($responses as $response) {
            $response->assertRedirect();
        }
        
        // Final locale should be the last one set
        expect(session('locale'))->toBe('ru');
    }

    /**
     * Test locale switching with different HTTP methods
     */
    public function test_locale_switching_http_methods(): void
    {
        // GET should work
        $response = $this->get('/language/lt');
        $response->assertRedirect();
        
        // POST should not be allowed (method not allowed)
        $response = $this->post('/language/en');
        $response->assertMethodNotAllowed();
        
        // PUT should not be allowed
        $response = $this->put('/language/ru');
        $response->assertMethodNotAllowed();
        
        // DELETE should not be allowed
        $response = $this->delete('/language/lt');
        $response->assertMethodNotAllowed();
    }

    /**
     * Test locale switching with middleware groups
     */
    public function test_locale_switching_middleware_groups(): void
    {
        // Language switching route should use 'web' middleware
        $response = $this->get('/language/en');
        
        // Should have session (from web middleware)
        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');
    }

    /**
     * Test edge cases and error conditions
     */
    public function test_edge_cases_and_error_conditions(): void
    {
        // Test empty locale parameter
        $response = $this->get('/language/');
        $response->assertNotFound();
        
        // Test very long locale string
        $longLocale = str_repeat('a', 1000);
        $response = $this->get("/language/{$longLocale}");
        $response->assertNotFound();
        
        // Test locale with special characters
        $response = $this->get('/language/en-US');
        $response->assertNotFound();
        
        // Test numeric locale
        $response = $this->get('/language/123');
        $response->assertNotFound();
    }

    /**
     * Test locale system integration with authentication
     */
    public function test_locale_system_with_authentication(): void
    {
        // Test unauthenticated user
        $response = $this->get('/language/lt');
        $response->assertRedirect();
        $response->assertSessionHas('locale', 'lt');
        
        // Test authenticated user without locale preference
        $user = User::factory()->create(['locale' => null]);
        $response = $this->actingAs($user)->get('/language/en');
        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');
        expect($user->fresh()->locale)->toBe('en');
        
        // Test authenticated user with existing locale preference
        $user = User::factory()->create(['locale' => 'ru']);
        $response = $this->actingAs($user)->get('/language/lt');
        $response->assertRedirect();
        $response->assertSessionHas('locale', 'lt');
        expect($user->fresh()->locale)->toBe('lt');
    }
}