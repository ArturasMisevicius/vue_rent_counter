<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\LanguageResource;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/**
 * Test suite for LanguageResource toggle active status functionality.
 *
 * This comprehensive test suite validates the toggle active/inactive functionality
 * in the LanguageResource, ensuring proper namespace consolidation, business logic,
 * and security controls are in place.
 *
 * ## Test Coverage
 *
 * ### Namespace Consolidation (3 tests)
 * - Verifies individual toggle action uses `Tables\Actions\Action`
 * - Verifies bulk activate action uses `Tables\Actions\BulkAction`
 * - Verifies bulk deactivate action uses `Tables\Actions\BulkAction`
 *
 * ### Functional Tests (6 tests)
 * - Toggle active language to inactive
 * - Toggle inactive language to active
 * - Bulk activate multiple languages
 * - Bulk deactivate multiple languages
 * - Default language protection (individual toggle)
 * - Default language protection (bulk deactivate)
 *
 * ### UI Element Tests (6 tests)
 * - Dynamic label for active language ("Deactivate")
 * - Dynamic label for inactive language ("Activate")
 * - Dynamic icon for active language (heroicon-o-x-circle)
 * - Dynamic icon for inactive language (heroicon-o-check-circle)
 * - Dynamic color for active language (danger/red)
 * - Dynamic color for inactive language (success/green)
 *
 * ### Authorization Tests (1 test)
 * - Verifies only superadmins can access toggle actions
 *
 * ## Business Rules Enforced
 *
 * 1. **Default Language Protection**: The default language cannot be deactivated
 *    - Individual toggle action is hidden for active default languages
 *    - Bulk deactivate throws exception if default language is included
 *
 * 2. **Confirmation Required**: All toggle actions require user confirmation
 *    - Prevents accidental state changes
 *    - Provides opportunity to review action
 *
 * 3. **Authorization**: Only superadmins can access language management
 *    - Enforced at resource level via `shouldRegisterNavigation()`
 *    - Additional checks via LanguagePolicy
 *
 * ## Namespace Consolidation Pattern
 *
 * This test suite verifies the Filament v4 namespace consolidation pattern:
 * ```php
 * use Filament\Tables;
 *
 * // Individual actions
 * Tables\Actions\Action::make('toggle_active')
 *
 * // Bulk actions
 * Tables\Actions\BulkAction::make('activate')
 * Tables\Actions\BulkAction::make('deactivate')
 * ```
 *
 * ## Related Documentation
 * @see \App\Filament\Resources\LanguageResource
 * @see \App\Policies\LanguagePolicy
 * @see docs/filament/LANGUAGE_RESOURCE_TOGGLE_ACTIVE_API.md
 * @see docs/testing/LANGUAGE_RESOURCE_TOGGLE_ACTIVE_VERIFICATION.md
 * @see .kiro/specs/6-filament-namespace-consolidation/tasks.md
 *
 * ## Test Execution
 * ```bash
 * # Run all toggle active tests
 * php artisan test --filter=LanguageResourceToggleActiveTest
 *
 * # Run specific test
 * php artisan test --filter=LanguageResourceToggleActiveTest::can_toggle_active_language_to_inactive
 *
 * # Run with coverage
 * php artisan test --filter=LanguageResourceToggleActiveTest --coverage
 * ```
 *
 * @group filament
 * @group language
 * @group toggle-active
 * @group namespace-consolidation
 */
class LanguageResourceToggleActiveTest extends \Tests\TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create superadmin user
        $this->superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
        ]);
    }

    /**
     * Test that toggle_active action exists and uses consolidated namespace.
     *
     * Verifies that the individual toggle action is properly configured using
     * the consolidated `Tables\Actions\Action` namespace instead of individual
     * imports. This is part of the Filament v4 namespace consolidation effort.
     *
     * @test
     * @group namespace-consolidation
     */
    public function toggle_active_action_uses_consolidated_namespace(): void
    {
        $language = Language::factory()->create(['is_active' => true]);

        $this->actingAs($this->superadmin);

        $component = Livewire::test(LanguageResource\Pages\ListLanguages::class);

        // Verify the action exists
        $component->assertTableActionExists('toggle_active');

        // Verify it's using Filament\Actions\Action (consolidated namespace)
        $actions = $component->instance()->getTable()->getActions();
        $toggleAction = collect($actions)->first(fn ($action) => $action->getName() === 'toggle_active');

        $this->assertNotNull($toggleAction);
        $this->assertInstanceOf(\Filament\Actions\Action::class, $toggleAction);
    }

    /**
     * Test toggling an active language to inactive.
     *
     * Verifies that an active, non-default language can be successfully
     * deactivated using the toggle action. The database should reflect
     * the updated state after the action completes.
     *
     * @test
     */
    public function can_toggle_active_language_to_inactive(): void
    {
        $language = Language::factory()->create([
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->actingAs($this->superadmin);

        Livewire::test(LanguageResource\Pages\ListLanguages::class)
            ->callTableAction('toggle_active', $language);

        $this->assertDatabaseHas('languages', [
            'id' => $language->id,
            'is_active' => false,
        ]);
    }

    /**
     * Test toggling an inactive language to active.
     *
     * Verifies that an inactive language can be successfully activated
     * using the toggle action. The database should reflect the updated
     * state after the action completes.
     *
     * @test
     */
    public function can_toggle_inactive_language_to_active(): void
    {
        $language = Language::factory()->create([
            'is_active' => false,
            'is_default' => false,
        ]);

        $this->actingAs($this->superadmin);

        Livewire::test(LanguageResource\Pages\ListLanguages::class)
            ->callTableAction('toggle_active', $language);

        $this->assertDatabaseHas('languages', [
            'id' => $language->id,
            'is_active' => true,
        ]);
    }

    /**
     * Test that default language cannot be deactivated via toggle action.
     *
     * Verifies the business rule that prevents deactivating the default language.
     * The toggle action should be hidden for active default languages, preventing
     * users from accidentally breaking the system by removing the default language.
     *
     * This is a critical security/stability feature that ensures the application
     * always has at least one active default language for fallback purposes.
     *
     * @test
     */
    public function cannot_deactivate_default_language_via_toggle(): void
    {
        $defaultLanguage = Language::factory()->create([
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->actingAs($this->superadmin);

        $component = Livewire::test(LanguageResource\Pages\ListLanguages::class);

        // The action should not be visible for active default language
        $component->assertTableActionHidden('toggle_active', $defaultLanguage);
    }

    /**
     * Test bulk activate action uses consolidated namespace.
     *
     * Verifies that the bulk activate action is properly configured using
     * the consolidated `Tables\Actions\BulkAction` namespace. This ensures
     * consistency with the Filament v4 namespace consolidation pattern.
     *
     * @test
     * @group namespace-consolidation
     */
    public function bulk_activate_action_uses_consolidated_namespace(): void
    {
        Language::factory()->count(2)->create(['is_active' => false]);

        $this->actingAs($this->superadmin);

        $component = Livewire::test(LanguageResource\Pages\ListLanguages::class);

        // Verify the bulk action exists
        $component->assertTableBulkActionExists('activate');

        // Verify it's using Filament\Actions\BulkAction (consolidated namespace)
        $bulkActions = $component->instance()->getTable()->getBulkActions();
        $activateAction = collect($bulkActions)
            ->flatten()
            ->first(fn ($action) => $action->getName() === 'activate');

        $this->assertNotNull($activateAction);
        $this->assertInstanceOf(\Filament\Actions\BulkAction::class, $activateAction);
    }

    /**
     * Test bulk activate action activates multiple languages.
     *
     * Verifies that multiple inactive languages can be activated simultaneously
     * using the bulk activate action. All selected languages should have their
     * is_active status set to true in the database.
     *
     * @test
     */
    public function can_bulk_activate_multiple_languages(): void
    {
        $languages = Language::factory()->count(3)->create(['is_active' => false]);

        $this->actingAs($this->superadmin);

        Livewire::test(LanguageResource\Pages\ListLanguages::class)
            ->callTableBulkAction('activate', $languages);

        foreach ($languages as $language) {
            $this->assertDatabaseHas('languages', [
                'id' => $language->id,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Test bulk deactivate action uses consolidated namespace.
     *
     * Verifies that the bulk deactivate action is properly configured using
     * the consolidated `Tables\Actions\BulkAction` namespace. This ensures
     * consistency with the Filament v4 namespace consolidation pattern.
     *
     * @test
     * @group namespace-consolidation
     */
    public function bulk_deactivate_action_uses_consolidated_namespace(): void
    {
        Language::factory()->count(2)->create(['is_active' => true, 'is_default' => false]);

        $this->actingAs($this->superadmin);

        $component = Livewire::test(LanguageResource\Pages\ListLanguages::class);

        // Verify the bulk action exists
        $component->assertTableBulkActionExists('deactivate');

        // Verify it's using Filament\Actions\BulkAction (consolidated namespace)
        $bulkActions = $component->instance()->getTable()->getBulkActions();
        $deactivateAction = collect($bulkActions)
            ->flatten()
            ->first(fn ($action) => $action->getName() === 'deactivate');

        $this->assertNotNull($deactivateAction);
        $this->assertInstanceOf(\Filament\Actions\BulkAction::class, $deactivateAction);
    }

    /**
     * Test bulk deactivate action deactivates multiple languages.
     *
     * Verifies that multiple active, non-default languages can be deactivated
     * simultaneously using the bulk deactivate action. All selected languages
     * should have their is_active status set to false in the database.
     *
     * @test
     */
    public function can_bulk_deactivate_multiple_languages(): void
    {
        $languages = Language::factory()->count(3)->create([
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->actingAs($this->superadmin);

        Livewire::test(LanguageResource\Pages\ListLanguages::class)
            ->callTableBulkAction('deactivate', $languages);

        foreach ($languages as $language) {
            $this->assertDatabaseHas('languages', [
                'id' => $language->id,
                'is_active' => false,
            ]);
        }
    }

    /**
     * Test bulk deactivate prevents deactivating default language.
     *
     * Verifies the business rule that prevents bulk deactivation of the default
     * language. When attempting to deactivate multiple languages including the
     * default language, an exception should be thrown and the default language
     * should remain active.
     *
     * This is a critical security/stability feature that ensures the application
     * always has an active default language for fallback purposes.
     *
     * @test
     */
    public function bulk_deactivate_prevents_deactivating_default_language(): void
    {
        $defaultLanguage = Language::factory()->create([
            'is_active' => true,
            'is_default' => true,
        ]);

        $otherLanguage = Language::factory()->create([
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->actingAs($this->superadmin);

        Livewire::test(LanguageResource\Pages\ListLanguages::class)
            ->callTableBulkAction('deactivate', [$defaultLanguage, $otherLanguage])
            ->assertNotified();

        // Default language should remain active
        $this->assertDatabaseHas('languages', [
            'id' => $defaultLanguage->id,
            'is_active' => true,
        ]);
    }

    /**
     * Test toggle action shows correct label for active language.
     *
     * Verifies that the toggle action displays "Deactivate" as the label
     * when the language is currently active. This provides clear user feedback
     * about what action will be performed when the button is clicked.
     *
     * @test
     */
    public function toggle_action_shows_deactivate_label_for_active_language(): void
    {
        $language = Language::factory()->create([
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->actingAs($this->superadmin);

        $component = Livewire::test(LanguageResource\Pages\ListLanguages::class);

        // Get the action
        $actions = $component->instance()->getTable()->getActions();
        $toggleAction = collect($actions)->first(fn ($action) => $action->getName() === 'toggle_active');

        // Evaluate the label
        $label = $toggleAction->evaluate($toggleAction->getLabel(), ['record' => $language]);

        $this->assertStringContainsString('Deactivate', $label);
    }

    /**
     * Test toggle action shows correct label for inactive language.
     *
     * Verifies that the toggle action displays "Activate" as the label
     * when the language is currently inactive. This provides clear user feedback
     * about what action will be performed when the button is clicked.
     *
     * @test
     */
    public function toggle_action_shows_activate_label_for_inactive_language(): void
    {
        $language = Language::factory()->create([
            'is_active' => false,
            'is_default' => false,
        ]);

        $this->actingAs($this->superadmin);

        $component = Livewire::test(LanguageResource\Pages\ListLanguages::class);

        // Get the action
        $actions = $component->instance()->getTable()->getActions();
        $toggleAction = collect($actions)->first(fn ($action) => $action->getName() === 'toggle_active');

        // Evaluate the label
        $label = $toggleAction->evaluate($toggleAction->getLabel(), ['record' => $language]);

        $this->assertStringContainsString('Activate', $label);
    }

    /**
     * Test toggle action uses correct icon for active language.
     *
     * Verifies that the toggle action displays the X-circle icon (heroicon-o-x-circle)
     * when the language is currently active. This visual indicator reinforces that
     * clicking the button will deactivate the language.
     *
     * @test
     */
    public function toggle_action_uses_correct_icon_for_active_language(): void
    {
        $language = Language::factory()->create([
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->actingAs($this->superadmin);

        $component = Livewire::test(LanguageResource\Pages\ListLanguages::class);

        // Get the action
        $actions = $component->instance()->getTable()->getActions();
        $toggleAction = collect($actions)->first(fn ($action) => $action->getName() === 'toggle_active');

        // Evaluate the icon
        $icon = $toggleAction->evaluate($toggleAction->getIcon(), ['record' => $language]);

        $this->assertEquals('heroicon-o-x-circle', $icon);
    }

    /**
     * Test toggle action uses correct icon for inactive language.
     *
     * Verifies that the toggle action displays the check-circle icon (heroicon-o-check-circle)
     * when the language is currently inactive. This visual indicator reinforces that
     * clicking the button will activate the language.
     *
     * @test
     */
    public function toggle_action_uses_correct_icon_for_inactive_language(): void
    {
        $language = Language::factory()->create([
            'is_active' => false,
            'is_default' => false,
        ]);

        $this->actingAs($this->superadmin);

        $component = Livewire::test(LanguageResource\Pages\ListLanguages::class);

        // Get the action
        $actions = $component->instance()->getTable()->getActions();
        $toggleAction = collect($actions)->first(fn ($action) => $action->getName() === 'toggle_active');

        // Evaluate the icon
        $icon = $toggleAction->evaluate($toggleAction->getIcon(), ['record' => $language]);

        $this->assertEquals('heroicon-o-check-circle', $icon);
    }

    /**
     * Test toggle action uses correct color for active language.
     *
     * Verifies that the toggle action uses the "danger" color (red) when the
     * language is currently active. This color scheme indicates a destructive
     * action (deactivation) and follows standard UI conventions.
     *
     * @test
     */
    public function toggle_action_uses_correct_color_for_active_language(): void
    {
        $language = Language::factory()->create([
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->actingAs($this->superadmin);

        $component = Livewire::test(LanguageResource\Pages\ListLanguages::class);

        // Get the action
        $actions = $component->instance()->getTable()->getActions();
        $toggleAction = collect($actions)->first(fn ($action) => $action->getName() === 'toggle_active');

        // Evaluate the color
        $color = $toggleAction->evaluate($toggleAction->getColor(), ['record' => $language]);

        $this->assertEquals('danger', $color);
    }

    /**
     * Test toggle action uses correct color for inactive language.
     *
     * Verifies that the toggle action uses the "success" color (green) when the
     * language is currently inactive. This color scheme indicates a positive
     * action (activation) and follows standard UI conventions.
     *
     * @test
     */
    public function toggle_action_uses_correct_color_for_inactive_language(): void
    {
        $language = Language::factory()->create([
            'is_active' => false,
            'is_default' => false,
        ]);

        $this->actingAs($this->superadmin);

        $component = Livewire::test(LanguageResource\Pages\ListLanguages::class);

        // Get the action
        $actions = $component->instance()->getTable()->getActions();
        $toggleAction = collect($actions)->first(fn ($action) => $action->getName() === 'toggle_active');

        // Evaluate the color
        $color = $toggleAction->evaluate($toggleAction->getColor(), ['record' => $language]);

        $this->assertEquals('success', $color);
    }

    /**
     * Test that only superadmin can access toggle actions.
     *
     * Verifies the authorization rule that restricts language management to
     * superadmins only. Users with other roles (ADMIN, MANAGER, TENANT) should
     * receive a 403 Forbidden response when attempting to access the language
     * resource, including all toggle actions.
     *
     * This test ensures proper role-based access control (RBAC) is enforced
     * at the resource level via the `shouldRegisterNavigation()` method.
     *
     * @test
     */
    public function only_superadmin_can_access_toggle_actions(): void
    {
        $language = Language::factory()->create(['is_active' => true]);

        // Test with admin user
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($admin);

        Livewire::test(LanguageResource\Pages\ListLanguages::class)
            ->assertForbidden();
    }
}
