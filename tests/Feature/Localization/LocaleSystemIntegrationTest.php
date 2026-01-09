<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use App\Enums\UserRole;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * Locale System Integration Tests
 * 
 * Tests the complete integration of the localization system including:
 * - Locale switching and persistence across requests
 * - Session management
 * - User preference updates
 * - Middleware integration
 * - NavigationComposer integration
 */
final class LocaleSystemIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed languages for testing
        $this->seed(\Database\Seeders\LanguageSeeder::class);
        
        // Register the language switch route for testing
        Route::get('/language/{locale}', [\App\Http\Controllers\LanguageController::class, 'switch'])
            ->name('language.switch');
    }

    /**
     * Test locale system integration - can switch locale and persist across requests
     */
    public function test_can_switch_locale_and_persist_across_requests(): void
    {
        // Start with default locale
        expect(app()->getLocale())->toBe('en');
        
        // Switch to Lithuanian
        $response = $this->get('/language/lt');
        
        $response->assertRedirect();
        $response->assertSessionHas('locale', 'lt');
        expect(app()->getLocale())->toBe('lt');
        
        // Make another request - locale should persist
        $response = $this->get('/');
        expect(app()->getLocale())->toBe('lt');
        
        // Switch to Russian
        $response = $this->get('/language/ru');
        
        $response->assertRedirect();
        $response->assertSessionHas('locale', 'ru');
        expect(app()->getLocale())->toBe('ru');
        
        // Make another request - new locale should persist
        $response = $this->get('/');
        expect(app()->getLocale())->toBe('ru');
    }

    /**
     * Test locale switching with authenticated user updates preferences
     */
    public function test_locale_switching_with_authenticated_user_updates_preferences(): void
    {
        $user = User::factory()->create(['locale' => 'en']);
        
        $response = $this->actingAs($user)->get('/language/lt');
        
        $response->assertRedirect();
        $response->assertSessionHas('locale', 'lt');
        
        // User preference should be updated
        expect($user->fresh()->locale)->toBe('lt');
        expect(app()->getLocale())->toBe('lt');
    }

    /**
     * Test locale switching with invalid locale returns 404
     */
    public function test_locale_switching_with_invalid_locale_returns_404(): void
    {
        $response = $this->get('/language/invalid');
        
        $response->assertNotFound();
        
        // Locale should remain unchanged
        expect(app()->getLocale())->toBe('en');
        expect(session('locale'))->toBeNull();
    }

    /**
     * Test locale switching redirects back to previous page
     */
    public function test_locale_switching_redirects_back_to_previous_page(): void
    {
        $response = $this->from('/dashboard')->get('/language/lt');
        
        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('locale', 'lt');
    }

    /**
     * Test locale switching with NavigationComposer integration
     */
    public function test_locale_switching_with_navigation_composer_integration(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($user);
        
        // Switch locale
        $this->get('/language/lt');
        
        // Create a view that uses NavigationComposer
        $view = view('layouts.app');
        
        // The view should have the correct locale data
        $viewData = $view->getData();
        
        expect($viewData['currentLocale'])->toBe('lt');
        expect($viewData['languages'])->toHaveCount(3);
        expect($viewData['showTopLocaleSwitcher'])->toBeTrue(); // Admin should see switcher
    }

    /**
     * Test locale switching security - prevents XSS
     */
    public function test_locale_switching_prevents_xss(): void
    {
        $maliciousLocale = '<script>alert("xss")</script>';
        
        $response = $this->get("/language/{$maliciousLocale}");
        
        $response->assertNotFound();
        expect(session('locale'))->toBeNull();
    }

    /**
     * Test locale switching with different user roles
     */
    public function test_locale_switching_with_different_user_roles(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);
            
            $response = $this->actingAs($user)->get('/language/ru');
            
            $response->assertRedirect();
            $response->assertSessionHas('locale', 'ru');
            expect($user->fresh()->locale)->toBe('ru');
        }
    }

    /**
     * Test locale persistence across multiple requests
     */
    public function test_locale_persistence_across_multiple_requests(): void
    {
        // Set locale
        $this->get('/language/lt');
        expect(app()->getLocale())->toBe('lt');
        
        // Make multiple requests
        for ($i = 0; $i < 5; $i++) {
            $this->get('/');
            expect(app()->getLocale())->toBe('lt');
        }
        
        // Change locale
        $this->get('/language/ru');
        expect(app()->getLocale())->toBe('ru');
        
        // Make multiple requests with new locale
        for ($i = 0; $i < 5; $i++) {
            $this->get('/');
            expect(app()->getLocale())->toBe('ru');
        }
    }

    /**
     * Test locale switching with session regeneration
     */
    public function test_locale_switching_with_session_regeneration(): void
    {
        // Set initial locale
        $this->get('/language/lt');
        $initialSessionId = session()->getId();
        
        // Regenerate session (simulating login/logout)
        session()->regenerate();
        $newSessionId = session()->getId();
        
        expect($newSessionId)->not->toBe($initialSessionId);
        
        // Locale should be preserved
        expect(session('locale'))->toBe('lt');
        expect(app()->getLocale())->toBe('lt');
    }

    /**
     * Test locale switching performance
     */
    public function test_locale_switching_performance(): void
    {
        $startTime = microtime(true);
        
        // Perform multiple locale switches
        $locales = ['lt', 'en', 'ru'];
        for ($i = 0; $i < 10; $i++) {
            $locale = $locales[$i % 3];
            $this->get("/language/{locale}");
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // Should complete within reasonable time
        expect($duration)->toBeLessThan(2.0);
    }

    /**
     * Test locale switching with concurrent sessions
     */
    public function test_locale_switching_with_concurrent_sessions(): void
    {
        // Create two different users
        $user1 = User::factory()->create(['locale' => 'en']);
        $user2 = User::factory()->create(['locale' => 'en']);
        
        // User 1 switches to Lithuanian
        $this->actingAs($user1)->get('/language/lt');
        expect($user1->fresh()->locale)->toBe('lt');
        
        // User 2 switches to Russian
        $this->actingAs($user2)->get('/language/ru');
        expect($user2->fresh()->locale)->toBe('ru');
        
        // User preferences should be independent
        expect($user1->fresh()->locale)->toBe('lt');
        expect($user2->fresh()->locale)->toBe('ru');
    }

    /**
     * Test locale switching with middleware chain
     */
    public function test_locale_switching_with_middleware_chain(): void
    {
        // Switch locale
        $response = $this->get('/language/lt');
        
        $response->assertRedirect();
        $response->assertSessionHas('locale', 'lt');
        
        // Subsequent request should have locale set by middleware
        $this->get('/');
        expect(app()->getLocale())->toBe('lt');
    }

    /**
     * Test locale switching edge cases
     */
    public function test_locale_switching_edge_cases(): void
    {
        // Test empty locale
        $response = $this->get('/language/');
        $response->assertNotFound();
        
        // Test very long locale
        $longLocale = str_repeat('a', 100);
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
     * Test locale switching with flash messages
     */
    public function test_locale_switching_with_flash_messages(): void
    {
        $response = $this->get('/language/lt');
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        // Flash message should be in the new locale
        $flashMessage = session('success');
        expect($flashMessage)->toBeString();
        expect($flashMessage)->not->toBeEmpty();
    }

    /**
     * Test locale switching maintains other session data
     */
    public function test_locale_switching_maintains_other_session_data(): void
    {
        // Set some session data
        session(['test_data' => 'important_value']);
        session(['another_key' => 'another_value']);
        
        // Switch locale
        $this->get('/language/ru');
        
        // Other session data should be preserved
        expect(session('test_data'))->toBe('important_value');
        expect(session('another_key'))->toBe('another_value');
        expect(session('locale'))->toBe('ru');
    }
}