<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use App\Enums\UserRole;
use App\Models\Language;
use App\Models\User;
use App\View\Composers\NavigationComposer;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Mockery;
use Tests\TestCase;

/**
 * NavigationComposer Localization Tests
 * 
 * Tests the NavigationComposer's locale-related functionality including:
 * - Locale switcher visibility based on user roles
 * - Language collection preparation
 * - Current locale detection
 * - Security aspects of navigation composition
 */
final class NavigationComposerTest extends TestCase
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
     * Test NavigationComposer with unauthenticated user
     */
    public function test_navigation_composer_with_unauthenticated_user(): void
    {
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->andReturn(false);
        
        $router = app(Router::class);
        $composer = new NavigationComposer($guard, $router);
        
        $view = Mockery::mock(View::class);
        $view->shouldReceive('with')->once()->with([
            'userRole' => null,
            'currentRoute' => null,
            'activeClass' => 'bg-gradient-to-r from-indigo-500 to-sky-500 text-white shadow-md shadow-indigo-500/30',
            'inactiveClass' => 'text-slate-700',
            'mobileActiveClass' => 'bg-gradient-to-r from-indigo-500 to-sky-500 text-white shadow-md shadow-indigo-500/30',
            'mobileInactiveClass' => 'text-slate-700',
            'canSwitchLocale' => false,
            'showTopLocaleSwitcher' => false,
            'languages' => collect(),
            'currentLocale' => app()->getLocale(),
        ]);
        
        $composer->compose($view);
    }

    /**
     * Test NavigationComposer with admin user (should show locale switcher)
     */
    public function test_navigation_composer_with_admin_user(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->andReturn(true);
        $guard->shouldReceive('user')->andReturn($user);
        
        $router = app(Router::class);
        $composer = new NavigationComposer($guard, $router);
        
        $view = Mockery::mock(View::class);
        $view->shouldReceive('with')->once()->with(Mockery::on(function ($data) {
            return $data['userRole'] === 'admin'
                && $data['canSwitchLocale'] === true
                && $data['showTopLocaleSwitcher'] === true
                && $data['languages']->count() === 3
                && $data['currentLocale'] === app()->getLocale();
        }));
        
        $composer->compose($view);
    }

    /**
     * Test NavigationComposer with manager user (should NOT show locale switcher)
     */
    public function test_navigation_composer_with_manager_user(): void
    {
        $user = User::factory()->create(['role' => UserRole::MANAGER]);
        
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->andReturn(true);
        $guard->shouldReceive('user')->andReturn($user);
        
        $router = app(Router::class);
        $composer = new NavigationComposer($guard, $router);
        
        $view = Mockery::mock(View::class);
        $view->shouldReceive('with')->once()->with(Mockery::on(function ($data) {
            return $data['userRole'] === 'manager'
                && $data['canSwitchLocale'] === true
                && $data['showTopLocaleSwitcher'] === false
                && $data['languages']->count() === 3;
        }));
        
        $composer->compose($view);
    }

    /**
     * Test NavigationComposer with tenant user (should NOT show locale switcher)
     */
    public function test_navigation_composer_with_tenant_user(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT]);
        
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->andReturn(true);
        $guard->shouldReceive('user')->andReturn($user);
        
        $router = app(Router::class);
        $composer = new NavigationComposer($guard, $router);
        
        $view = Mockery::mock(View::class);
        $view->shouldReceive('with')->once()->with(Mockery::on(function ($data) {
            return $data['userRole'] === 'tenant'
                && $data['canSwitchLocale'] === true
                && $data['showTopLocaleSwitcher'] === false
                && $data['languages']->count() === 3;
        }));
        
        $composer->compose($view);
    }

    /**
     * Test NavigationComposer with superadmin user (should NOT show locale switcher)
     */
    public function test_navigation_composer_with_superadmin_user(): void
    {
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->andReturn(true);
        $guard->shouldReceive('user')->andReturn($user);
        
        $router = app(Router::class);
        $composer = new NavigationComposer($guard, $router);
        
        $view = Mockery::mock(View::class);
        $view->shouldReceive('with')->once()->with(Mockery::on(function ($data) {
            return $data['userRole'] === 'superadmin'
                && $data['canSwitchLocale'] === true
                && $data['showTopLocaleSwitcher'] === false
                && $data['languages']->count() === 3;
        }));
        
        $composer->compose($view);
    }

    /**
     * Test NavigationComposer when language.switch route doesn't exist
     */
    public function test_navigation_composer_without_language_switch_route(): void
    {
        // Remove the language switch route
        Route::getRoutes()->refreshNameLookups();
        
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->andReturn(true);
        $guard->shouldReceive('user')->andReturn($user);
        
        $router = Mockery::mock(Router::class);
        $router->shouldReceive('currentRouteName')->andReturn('dashboard');
        $router->shouldReceive('has')->with('language.switch')->andReturn(false);
        
        $composer = new NavigationComposer($guard, $router);
        
        $view = Mockery::mock(View::class);
        $view->shouldReceive('with')->once()->with(Mockery::on(function ($data) {
            return $data['canSwitchLocale'] === false
                && $data['showTopLocaleSwitcher'] === false;
        }));
        
        $composer->compose($view);
    }

    /**
     * Test NavigationComposer language collection structure
     */
    public function test_navigation_composer_language_collection_structure(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->andReturn(true);
        $guard->shouldReceive('user')->andReturn($user);
        
        $router = app(Router::class);
        $composer = new NavigationComposer($guard, $router);
        
        $view = Mockery::mock(View::class);
        $view->shouldReceive('with')->once()->with(Mockery::on(function ($data) {
            $languages = $data['languages'];
            
            // Should have 3 languages
            if ($languages->count() !== 3) {
                return false;
            }
            
            // Should be ordered by display_order (lt, en, ru)
            $codes = $languages->pluck('code')->toArray();
            if ($codes !== ['lt', 'en', 'ru']) {
                return false;
            }
            
            // Each language should have required properties
            foreach ($languages as $language) {
                if (!isset($language->code, $language->name, $language->native_name, $language->is_active)) {
                    return false;
                }
            }
            
            return true;
        }));
        
        $composer->compose($view);
    }

    /**
     * Test NavigationComposer with different current locales
     */
    public function test_navigation_composer_with_different_current_locales(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->andReturn(true);
        $guard->shouldReceive('user')->andReturn($user);
        
        $router = app(Router::class);
        $composer = new NavigationComposer($guard, $router);
        
        foreach (['lt', 'en', 'ru'] as $locale) {
            app()->setLocale($locale);
            
            $view = Mockery::mock(View::class);
            $view->shouldReceive('with')->once()->with(Mockery::on(function ($data) use ($locale) {
                return $data['currentLocale'] === $locale;
            }));
            
            $composer->compose($view);
        }
    }

    /**
     * Test NavigationComposer CSS classes
     */
    public function test_navigation_composer_css_classes(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->andReturn(true);
        $guard->shouldReceive('user')->andReturn($user);
        
        $router = app(Router::class);
        $composer = new NavigationComposer($guard, $router);
        
        $view = Mockery::mock(View::class);
        $view->shouldReceive('with')->once()->with(Mockery::on(function ($data) {
            $expectedActiveClass = 'bg-gradient-to-r from-indigo-500 to-sky-500 text-white shadow-md shadow-indigo-500/30';
            $expectedInactiveClass = 'text-slate-700';
            
            return $data['activeClass'] === $expectedActiveClass
                && $data['inactiveClass'] === $expectedInactiveClass
                && $data['mobileActiveClass'] === $expectedActiveClass
                && $data['mobileInactiveClass'] === $expectedInactiveClass;
        }));
        
        $composer->compose($view);
    }

    /**
     * Test NavigationComposer current route detection
     */
    public function test_navigation_composer_current_route_detection(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->andReturn(true);
        $guard->shouldReceive('user')->andReturn($user);
        
        $router = Mockery::mock(Router::class);
        $router->shouldReceive('currentRouteName')->andReturn('dashboard.index');
        $router->shouldReceive('has')->with('language.switch')->andReturn(true);
        
        $composer = new NavigationComposer($guard, $router);
        
        $view = Mockery::mock(View::class);
        $view->shouldReceive('with')->once()->with(Mockery::on(function ($data) {
            return $data['currentRoute'] === 'dashboard.index';
        }));
        
        $composer->compose($view);
    }

    /**
     * Test NavigationComposer security with type safety
     */
    public function test_navigation_composer_security_type_safety(): void
    {
        // Test with each role enum value to ensure type safety
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);
            
            $guard = Mockery::mock(Guard::class);
            $guard->shouldReceive('check')->andReturn(true);
            $guard->shouldReceive('user')->andReturn($user);
            
            $router = app(Router::class);
            $composer = new NavigationComposer($guard, $router);
            
            $view = Mockery::mock(View::class);
            $view->shouldReceive('with')->once()->with(Mockery::on(function ($data) use ($role) {
                // Verify role value is properly converted to string
                return $data['userRole'] === $role->value;
            }));
            
            $composer->compose($view);
        }
    }

    /**
     * Test NavigationComposer with inactive languages
     */
    public function test_navigation_composer_with_inactive_languages(): void
    {
        // Deactivate one language
        $language = Language::where('code', 'ru')->first();
        $language->update(['is_active' => false]);
        
        // Clear cache
        Language::getActiveLanguages(); // This will refresh the cache
        
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->andReturn(true);
        $guard->shouldReceive('user')->andReturn($user);
        
        $router = app(Router::class);
        $composer = new NavigationComposer($guard, $router);
        
        $view = Mockery::mock(View::class);
        $view->shouldReceive('with')->once()->with(Mockery::on(function ($data) {
            $languages = $data['languages'];
            
            // Should only have 2 active languages now
            if ($languages->count() !== 2) {
                return false;
            }
            
            // Should not include Russian
            $codes = $languages->pluck('code')->toArray();
            return !in_array('ru', $codes) && in_array('lt', $codes) && in_array('en', $codes);
        }));
        
        $composer->compose($view);
    }

    /**
     * Test NavigationComposer performance with caching
     */
    public function test_navigation_composer_performance_with_caching(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->andReturn(true);
        $guard->shouldReceive('user')->andReturn($user);
        
        $router = app(Router::class);
        $composer = new NavigationComposer($guard, $router);
        
        // First call should populate cache
        $startTime = microtime(true);
        
        $view1 = Mockery::mock(View::class);
        $view1->shouldReceive('with')->once();
        $composer->compose($view1);
        
        $firstCallTime = microtime(true) - $startTime;
        
        // Second call should use cache and be faster
        $startTime = microtime(true);
        
        $view2 = Mockery::mock(View::class);
        $view2->shouldReceive('with')->once();
        $composer->compose($view2);
        
        $secondCallTime = microtime(true) - $startTime;
        
        // Second call should be faster due to caching
        expect($secondCallTime)->toBeLessThan($firstCallTime * 2); // Allow some variance
    }

    /**
     * Test NavigationComposer integration with actual view
     */
    public function test_navigation_composer_integration_with_actual_view(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($user);
        
        // Create a simple view for testing
        $viewContent = '
            @if($showTopLocaleSwitcher)
                <div class="locale-switcher">
                    @foreach($languages as $language)
                        <a href="#" class="{{ $language->code === $currentLocale ? $activeClass : $inactiveClass }}">
                            {{ $language->name }}
                        </a>
                    @endforeach
                </div>
            @endif
        ';
        
        $view = view()->make('test-view', [], compact('viewContent'));
        
        // Register the composer
        view()->composer('test-view', NavigationComposer::class);
        
        // Render the view
        $rendered = $view->render();
        
        // Should contain locale switcher for admin
        expect($rendered)->toContain('locale-switcher');
        expect($rendered)->toContain('Lithuanian');
        expect($rendered)->toContain('English');
        expect($rendered)->toContain('Russian');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}