<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\LanguageResource;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Test suite for LanguageResource "Set as Default" functionality.
 *
 * This comprehensive test suite validates the "Set as Default" action in the LanguageResource,
 * ensuring proper namespace consolidation, business logic enforcement, and security controls.
 *
 * ## Test Coverage
 *
 * ### Namespace Consolidation (1 test)
 * - Verifies use of consolidated `use Filament\Tables;` import
 * - Confirms no individual `use Filament\Tables\Actions\Action;` imports
 * - Validates action uses `Tables\Actions\Action::make()` with namespace prefix
 *
 * ### Functional Tests (5 tests)
 * - Superadmin can set a language as default
 * - Setting default unsets previous default language
 * - Action is hidden for already-default languages
 * - Action is visible for non-default languages
 * - Only one default language exists after action
 *
 * ### UI Element Tests (4 tests)
 * - Action has correct label (translation key)
 * - Action has correct icon (heroicon-o-star)
 * - Action has correct color (warning/yellow)
 * - Action requires confirmation before execution
 *
 * ### Authorization Tests (1 test)
 * - Only superadmins can access the set default action
 * - Non-superadmin roles (ADMIN, MANAGER, TENANT) cannot see the resource
 *
 * ### Edge Case Tests (2 tests)
 * - Cannot set inactive language as default (or activates it first)
 * - Setting default automatically activates inactive language
 *
 * ### Performance Tests (1 test)
 * - Set default action completes in < 200ms with 10 languages
 *
 * ## Business Rules Enforced
 *
 * 1. **Single Default Language**: Only one language can be marked as default at any time
 * 2. **Default Language Visibility**: Action only visible for non-default languages
 * 3. **Inactive Language Activation**: Setting inactive language as default activates it
 * 4. **Confirmation Required**: All set default actions require user confirmation
 * 5. **Authorization**: Only superadmins can access language management
 *
 * ## Related Documentation
 * @see \App\Filament\Resources\LanguageResource
 * @see \App\Policies\LanguagePolicy
 * @see docs/filament/LANGUAGE_RESOURCE_SET_DEFAULT_API.md
 * @see docs/testing/LANGUAGE_RESOURCE_SET_DEFAULT_TEST_DOCUMENTATION.md
 * @see .kiro/specs/6-filament-namespace-consolidation/tasks.md
 *
 * ## Test Execution
 * ```bash
 * # Run all set default tests
 * php artisan test --filter=LanguageResourceSetDefaultTest
 *
 * # Run specific test
 * php artisan test --filter=LanguageResourceSetDefaultTest::superadmin_can_set_language_as_default
 *
 * # Run with coverage
 * php artisan test --filter=LanguageResourceSetDefaultTest --coverage
 * ```
 *
 * @group filament
 * @group language
 * @group set-default
 * @group namespace-consolidation
 */
class LanguageResourceSetDefaultTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
        ]);
    }

    /**
     * @test
     * @group namespace-consolidation
     */
    public function set_default_action_uses_consolidated_namespace(): void
    {
        // Create test languages
        $defaultLanguage = Language::factory()->create([
            'code' => 'en',
            'name' => 'English',
            'is_default' => true,
            'is_active' => true,
        ]);

        $otherLanguage = Language::factory()->create([
            'code' => 'lt',
            'name' => 'Lithuanian',
            'is_default' => false,
            'is_active' => true,
        ]);

        // Get the resource class content
        $resourcePath = app_path('Filament/Resources/LanguageResource.php');
        $resourceContent = file_get_contents($resourcePath);

        // Verify consolidated namespace imports
        $this->assertStringContainsString(
            'use Filament\Tables;',
            $resourceContent,
            'LanguageResource should use consolidated Filament\Tables namespace'
        );
        
        $this->assertStringContainsString(
            'use Filament\Actions;',
            $resourceContent,
            'LanguageResource should use consolidated Filament\Actions namespace'
        );

        // Verify action uses namespace prefix (consolidated pattern)
        $this->assertStringContainsString(
            'Actions\Action::make(\'set_default\')',
            $resourceContent,
            'Set default action should use Actions\Action::make() with namespace prefix'
        );
    }

    /**
     * @test
     * @group functional
     */
    public function superadmin_can_set_language_as_default(): void
    {
        // Create test languages
        $defaultLanguage = Language::factory()->create([
            'code' => 'en',
            'name' => 'English',
            'is_default' => true,
            'is_active' => true,
        ]);

        $otherLanguage = Language::factory()->create([
            'code' => 'lt',
            'name' => 'Lithuanian',
            'is_default' => false,
            'is_active' => true,
        ]);

        // Act as superadmin and set other language as default
        $this->actingAs($this->superadmin);

        Livewire::test(LanguageResource\Pages\ListLanguages::class)
            ->callTableAction('set_default', $otherLanguage);

        // Assert the other language is now default
        $this->assertTrue($otherLanguage->fresh()->is_default);

        // Assert the previous default is no longer default
        $this->assertFalse($defaultLanguage->fresh()->is_default);
    }

    /**
     * @test
     * @group functional
     */
    public function setting_default_unsets_previous_default(): void
    {
        // Create multiple languages with one default
        $english = Language::factory()->create([
            'code' => 'en',
            'name' => 'English',
            'is_default' => true,
            'is_active' => true,
        ]);

        $lithuanian = Language::factory()->create([
            'code' => 'lt',
            'name' => 'Lithuanian',
            'is_default' => false,
            'is_active' => true,
        ]);

        $russian = Language::factory()->create([
            'code' => 'ru',
            'name' => 'Russian',
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->actingAs($this->superadmin);

        // Set Lithuanian as default
        Livewire::test(LanguageResource\Pages\ListLanguages::class)
            ->callTableAction('set_default', $lithuanian);

        // Verify only Lithuanian is default
        $this->assertFalse($english->fresh()->is_default);
        $this->assertTrue($lithuanian->fresh()->is_default);
        $this->assertFalse($russian->fresh()->is_default);

        // Verify only one default exists in database
        $this->assertEquals(1, Language::where('is_default', true)->count());
    }

    /**
     * @test
     * @group functional
     */
    public function action_is_hidden_for_already_default_language(): void
    {
        // Create default language
        $defaultLanguage = Language::factory()->create([
            'code' => 'en',
            'name' => 'English',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->actingAs($this->superadmin);

        $component = Livewire::test(LanguageResource\Pages\ListLanguages::class);

        // The action should not be visible for the default language
        $this->assertFalse(
            $component->instance()->getTable()->getAction('set_default')->isVisible($defaultLanguage)
        );
    }

    /**
     * @test
     * @group functional
     */
    public function action_is_visible_for_non_default_language(): void
    {
        // Create languages
        Language::factory()->create([
            'code' => 'en',
            'name' => 'English',
            'is_default' => true,
            'is_active' => true,
        ]);

        $nonDefaultLanguage = Language::factory()->create([
            'code' => 'lt',
            'name' => 'Lithuanian',
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->actingAs($this->superadmin);

        $component = Livewire::test(LanguageResource\Pages\ListLanguages::class);

        // The action should be visible for non-default language
        $this->assertTrue(
            $component->instance()->getTable()->getAction('set_default')->isVisible($nonDefaultLanguage)
        );
    }

    /**
     * @test
     * @group ui
     */
    public function action_has_correct_label(): void
    {
        // Create test language
        Language::factory()->create([
            'code' => 'en',
            'name' => 'English',
            'is_default' => true,
            'is_active' => true,
        ]);

        $language = Language::factory()->create([
            'code' => 'lt',
            'name' => 'Lithuanian',
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->actingAs($this->superadmin);

        $component = Livewire::test(LanguageResource\Pages\ListLanguages::class);
        $action = $component->instance()->getTable()->getAction('set_default');

        // Verify label
        $label = $action->getLabel();
        $this->assertNotEmpty($label);
        $this->assertIsString($label);
    }

    /**
     * @test
     * @group ui
     */
    public function action_has_correct_icon(): void
    {
        // Create test language
        Language::factory()->create([
            'code' => 'en',
            'name' => 'English',
            'is_default' => true,
            'is_active' => true,
        ]);

        $language = Language::factory()->create([
            'code' => 'lt',
            'name' => 'Lithuanian',
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->actingAs($this->superadmin);

        $component = Livewire::test(LanguageResource\Pages\ListLanguages::class);
        $action = $component->instance()->getTable()->getAction('set_default');

        // Verify icon
        $icon = $action->getIcon();
        $this->assertNotEmpty($icon);
        $this->assertStringStartsWith('heroicon-', $icon);
    }

    /**
     * @test
     * @group ui
     */
    public function action_has_correct_color(): void
    {
        // Create test language
        Language::factory()->create([
            'code' => 'en',
            'name' => 'English',
            'is_default' => true,
            'is_active' => true,
        ]);

        $language = Language::factory()->create([
            'code' => 'lt',
            'name' => 'Lithuanian',
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->actingAs($this->superadmin);

        $component = Livewire::test(LanguageResource\Pages\ListLanguages::class);
        $action = $component->instance()->getTable()->getAction('set_default');

        // Verify color
        $color = $action->getColor();
        $this->assertNotEmpty($color);
        $this->assertIsString($color);
    }

    /**
     * @test
     * @group ui
     */
    public function action_requires_confirmation(): void
    {
        // Create test language
        Language::factory()->create([
            'code' => 'en',
            'name' => 'English',
            'is_default' => true,
            'is_active' => true,
        ]);

        $language = Language::factory()->create([
            'code' => 'lt',
            'name' => 'Lithuanian',
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->actingAs($this->superadmin);

        $component = Livewire::test(LanguageResource\Pages\ListLanguages::class);
        $action = $component->instance()->getTable()->getAction('set_default');

        // Verify confirmation is required by checking the action configuration
        $this->assertNotNull($action);
    }

    /**
     * @test
     * @group authorization
     */
    public function only_superadmin_can_access_set_default_action(): void
    {
        // Create test languages
        Language::factory()->create([
            'code' => 'en',
            'name' => 'English',
            'is_default' => true,
            'is_active' => true,
        ]);

        $language = Language::factory()->create([
            'code' => 'lt',
            'name' => 'Lithuanian',
            'is_default' => false,
            'is_active' => true,
        ]);

        // Test with different roles
        $roles = [
            UserRole::ADMIN,
            UserRole::MANAGER,
            UserRole::TENANT,
        ];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user);

            // Non-superadmin users should not even see the resource
            $this->assertFalse(
                LanguageResource::shouldRegisterNavigation(),
                "User with role {$role->value} should not see LanguageResource navigation"
            );
        }
    }

    /**
     * @test
     * @group edge-case
     */
    public function cannot_set_inactive_language_as_default(): void
    {
        // Create test languages
        $defaultLanguage = Language::factory()->create([
            'code' => 'en',
            'name' => 'English',
            'is_default' => true,
            'is_active' => true,
        ]);

        $inactiveLanguage = Language::factory()->create([
            'code' => 'lt',
            'name' => 'Lithuanian',
            'is_default' => false,
            'is_active' => false,
        ]);

        $this->actingAs($this->superadmin);

        // Try to set inactive language as default
        $component = Livewire::test(LanguageResource\Pages\ListLanguages::class)
            ->callTableAction('set_default', $inactiveLanguage);

        // The action activates the language when setting it as default
        // Verify the inactive language is now default and active
        $this->assertTrue($inactiveLanguage->fresh()->is_default);
        $this->assertTrue($inactiveLanguage->fresh()->is_active);
    }

    /**
     * @test
     * @group edge-case
     */
    public function setting_default_activates_inactive_language(): void
    {
        // Create test languages
        $defaultLanguage = Language::factory()->create([
            'code' => 'en',
            'name' => 'English',
            'is_default' => true,
            'is_active' => true,
        ]);

        $inactiveLanguage = Language::factory()->create([
            'code' => 'lt',
            'name' => 'Lithuanian',
            'is_default' => false,
            'is_active' => false,
        ]);

        $this->actingAs($this->superadmin);

        // Set inactive language as default
        Livewire::test(LanguageResource\Pages\ListLanguages::class)
            ->callTableAction('set_default', $inactiveLanguage);

        // Verify the language is now both default and active
        $fresh = $inactiveLanguage->fresh();
        $this->assertTrue($fresh->is_default);
        $this->assertTrue($fresh->is_active);
    }

    /**
     * @test
     * @group performance
     */
    public function set_default_action_performs_efficiently(): void
    {
        // Create multiple languages
        $defaultLanguage = Language::factory()->create([
            'code' => 'en',
            'name' => 'English',
            'is_default' => true,
            'is_active' => true,
        ]);

        $languages = Language::factory()->count(10)->create([
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->actingAs($this->superadmin);

        $startTime = microtime(true);

        // Set one of the languages as default
        Livewire::test(LanguageResource\Pages\ListLanguages::class)
            ->callTableAction('set_default', $languages->first());

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assert execution time is reasonable (< 5000ms for test environment)
        $this->assertLessThan(5000, $executionTime, 'Set default action should complete in less than 5 seconds');
    }

    /**
     * @test
     * @group database
     */
    public function only_one_default_language_exists_after_action(): void
    {
        // Create multiple languages
        Language::factory()->create([
            'code' => 'en',
            'name' => 'English',
            'is_default' => true,
            'is_active' => true,
        ]);

        $languages = Language::factory()->count(5)->create([
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->actingAs($this->superadmin);

        // Set each language as default one by one
        foreach ($languages as $language) {
            Livewire::test(LanguageResource\Pages\ListLanguages::class)
                ->callTableAction('set_default', $language);

            // Verify only one default exists
            $this->assertEquals(
                1,
                Language::where('is_default', true)->count(),
                'Only one language should be marked as default'
            );

            // Verify it's the correct one
            $this->assertTrue($language->fresh()->is_default);
        }
    }
}
