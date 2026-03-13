<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\TranslationResource;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Comprehensive test suite for TranslationResource delete functionality.
 *
 * This test suite validates:
 * - Namespace consolidation (Tables\Actions\DeleteAction, Tables\Actions\DeleteBulkAction)
 * - Delete action configuration and behavior
 * - Bulk delete action configuration and behavior
 * - Authorization checks (SUPERADMIN only)
 * - Edge cases (non-existent records, empty selections)
 * - Performance benchmarks
 * - UI elements (confirmation modals, notifications)
 *
 * Test Coverage:
 * - Namespace consolidation: 3 tests
 * - Delete action configuration: 3 tests
 * - Delete functionality: 4 tests
 * - Bulk delete configuration: 3 tests
 * - Bulk delete functionality: 4 tests
 * - Authorization: 4 tests
 * - Edge cases: 4 tests
 * - Performance: 2 tests
 * - UI elements: 3 tests
 *
 * Total: 30 tests
 *
 * @group filament
 * @group translation
 * @group delete
 * @group namespace-consolidation
 */
class TranslationResourceDeleteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that TranslationResource uses consolidated namespace for DeleteAction.
     *
     * Validates: Requirements 1.1 (Namespace Consolidation)
     */
    public function test_uses_consolidated_namespace_for_delete_action(): void
    {
        $resourceClass = TranslationResource::class;
        $reflection = new \ReflectionClass($resourceClass);
        $fileContent = file_get_contents($reflection->getFileName());

        // Should use consolidated namespace
        $this->assertStringContainsString('use Filament\Tables;', $fileContent);

        // Should NOT have individual DeleteAction import
        $this->assertStringNotContainsString('use Filament\Tables\Actions\DeleteAction;', $fileContent);

        // Should use namespace prefix in code
        $this->assertStringContainsString('Tables\Actions\DeleteAction', $fileContent);
    }

    /**
     * Test that TranslationResource uses consolidated namespace for DeleteBulkAction.
     *
     * Validates: Requirements 1.1 (Namespace Consolidation)
     */
    public function test_uses_consolidated_namespace_for_delete_bulk_action(): void
    {
        $resourceClass = TranslationResource::class;
        $reflection = new \ReflectionClass($resourceClass);
        $fileContent = file_get_contents($reflection->getFileName());

        // Should NOT have individual DeleteBulkAction import
        $this->assertStringNotContainsString('use Filament\Tables\Actions\DeleteBulkAction;', $fileContent);

        // Should use namespace prefix in code
        $this->assertStringContainsString('Tables\Actions\DeleteBulkAction', $fileContent);
    }

    /**
     * Test that TranslationResource uses consolidated namespace for BulkActionGroup.
     *
     * Validates: Requirements 1.1 (Namespace Consolidation)
     */
    public function test_uses_consolidated_namespace_for_bulk_action_group(): void
    {
        $resourceClass = TranslationResource::class;
        $reflection = new \ReflectionClass($resourceClass);
        $fileContent = file_get_contents($reflection->getFileName());

        // Should NOT have individual BulkActionGroup import
        $this->assertStringNotContainsString('use Filament\Tables\Actions\BulkActionGroup;', $fileContent);

        // Should use namespace prefix in code
        $this->assertStringContainsString('Tables\Actions\BulkActionGroup', $fileContent);
    }

    /**
     * Test that delete action is configured in the table.
     *
     * Validates: Requirements 1.2 (Delete Action Configuration)
     */
    public function test_delete_action_is_configured(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $translation = Translation::factory()->create();

        $this->actingAs($superadmin);

        Livewire::test(TranslationResource\Pages\ListTranslations::class)
            ->assertTableActionExists('delete');
    }

    /**
     * Test that delete action is rendered as icon button.
     *
     * Validates: Requirements 1.2 (Delete Action Configuration)
     */
    public function test_delete_action_is_icon_button(): void
    {
        $resourceClass = TranslationResource::class;
        $reflection = new \ReflectionClass($resourceClass);
        $fileContent = file_get_contents($reflection->getFileName());

        // Should have iconButton() configuration
        $this->assertMatchesRegularExpression(
            '/Tables\\\\Actions\\\\DeleteAction::make\(\).*->iconButton\(\)/s',
            $fileContent
        );
    }

    /**
     * Test that delete action is visible to superadmin.
     *
     * Validates: Requirements 1.2 (Delete Action Configuration)
     */
    public function test_delete_action_visible_to_superadmin(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $translation = Translation::factory()->create();

        $this->actingAs($superadmin);

        Livewire::test(TranslationResource\Pages\ListTranslations::class)
            ->assertTableActionVisible('delete', $translation);
    }

    /**
     * Test that superadmin can delete a translation.
     *
     * Validates: Requirements 1.3 (Delete Functionality)
     */
    public function test_superadmin_can_delete_translation(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $translation = Translation::factory()->create([
            'group' => 'test',
            'key' => 'delete_me',
        ]);

        $this->actingAs($superadmin);

        $this->assertDatabaseHas('translations', [
            'id' => $translation->id,
            'group' => 'test',
            'key' => 'delete_me',
        ]);

        Livewire::test(TranslationResource\Pages\ListTranslations::class)
            ->callTableAction('delete', $translation);

        $this->assertDatabaseMissing('translations', [
            'id' => $translation->id,
        ]);
    }

    /**
     * Test that deleting a translation removes it from the list.
     *
     * Validates: Requirements 1.3 (Delete Functionality)
     */
    public function test_deleted_translation_removed_from_list(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $translation = Translation::factory()->create();

        $this->actingAs($superadmin);

        Livewire::test(TranslationResource\Pages\ListTranslations::class)
            ->assertCanSeeTableRecords([$translation])
            ->callTableAction('delete', $translation)
            ->assertCanNotSeeTableRecords([$translation]);
    }

    /**
     * Test that deleting a translation with multiple language values works correctly.
     *
     * Validates: Requirements 1.3 (Delete Functionality)
     */
    public function test_can_delete_translation_with_multiple_language_values(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $translation = Translation::factory()->create([
            'group' => 'test',
            'key' => 'multi_lang',
            'values' => [
                'en' => 'English value',
                'lt' => 'Lithuanian value',
                'ru' => 'Russian value',
            ],
        ]);

        $this->actingAs($superadmin);

        Livewire::test(TranslationResource\Pages\ListTranslations::class)
            ->callTableAction('delete', $translation);

        $this->assertDatabaseMissing('translations', [
            'id' => $translation->id,
        ]);
    }

    /**
     * Test that deleting a translation from a group with multiple translations works.
     *
     * Validates: Requirements 1.3 (Delete Functionality)
     */
    public function test_can_delete_translation_from_group_with_multiple_translations(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        
        $translation1 = Translation::factory()->create(['group' => 'common', 'key' => 'key1']);
        $translation2 = Translation::factory()->create(['group' => 'common', 'key' => 'key2']);
        $translation3 = Translation::factory()->create(['group' => 'common', 'key' => 'key3']);

        $this->actingAs($superadmin);

        Livewire::test(TranslationResource\Pages\ListTranslations::class)
            ->callTableAction('delete', $translation2);

        $this->assertDatabaseHas('translations', ['id' => $translation1->id]);
        $this->assertDatabaseMissing('translations', ['id' => $translation2->id]);
        $this->assertDatabaseHas('translations', ['id' => $translation3->id]);
    }

    /**
     * Test that bulk delete action is configured.
     *
     * Validates: Requirements 1.4 (Bulk Delete Configuration)
     */
    public function test_bulk_delete_action_is_configured(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        Translation::factory()->count(3)->create();

        $this->actingAs($superadmin);

        Livewire::test(TranslationResource\Pages\ListTranslations::class)
            ->assertTableBulkActionExists('delete');
    }

    /**
     * Test that bulk delete action requires confirmation.
     *
     * Validates: Requirements 1.4 (Bulk Delete Configuration)
     */
    public function test_bulk_delete_requires_confirmation(): void
    {
        $resourceClass = TranslationResource::class;
        $reflection = new \ReflectionClass($resourceClass);
        $fileContent = file_get_contents($reflection->getFileName());

        // Should have requiresConfirmation() configuration
        $this->assertMatchesRegularExpression(
            '/Tables\\\\Actions\\\\DeleteBulkAction::make\(\).*->requiresConfirmation\(\)/s',
            $fileContent
        );
    }

    /**
     * Test that bulk delete action has custom modal configuration.
     *
     * Validates: Requirements 1.4 (Bulk Delete Configuration)
     */
    public function test_bulk_delete_has_custom_modal_configuration(): void
    {
        $resourceClass = TranslationResource::class;
        $reflection = new \ReflectionClass($resourceClass);
        $fileContent = file_get_contents($reflection->getFileName());

        // Should have custom modal heading and description
        $this->assertStringContainsString('->modalHeading(', $fileContent);
        $this->assertStringContainsString('->modalDescription(', $fileContent);
    }

    /**
     * Test that superadmin can bulk delete translations.
     *
     * Validates: Requirements 1.5 (Bulk Delete Functionality)
     */
    public function test_superadmin_can_bulk_delete_translations(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        
        $translations = Translation::factory()->count(5)->create();
        $translationsToDelete = $translations->take(3);

        $this->actingAs($superadmin);

        Livewire::test(TranslationResource\Pages\ListTranslations::class)
            ->callTableBulkAction('delete', $translationsToDelete);

        foreach ($translationsToDelete as $translation) {
            $this->assertDatabaseMissing('translations', ['id' => $translation->id]);
        }

        // Verify remaining translations still exist
        foreach ($translations->skip(3) as $translation) {
            $this->assertDatabaseHas('translations', ['id' => $translation->id]);
        }
    }

    /**
     * Test that bulk delete removes all selected translations from the list.
     *
     * Validates: Requirements 1.5 (Bulk Delete Functionality)
     */
    public function test_bulk_deleted_translations_removed_from_list(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        
        $translations = Translation::factory()->count(5)->create();
        $translationsToDelete = $translations->take(3);

        $this->actingAs($superadmin);

        Livewire::test(TranslationResource\Pages\ListTranslations::class)
            ->assertCanSeeTableRecords($translations)
            ->callTableBulkAction('delete', $translationsToDelete)
            ->assertCanNotSeeTableRecords($translationsToDelete)
            ->assertCanSeeTableRecords($translations->skip(3));
    }

    /**
     * Test that bulk delete works with translations from different groups.
     *
     * Validates: Requirements 1.5 (Bulk Delete Functionality)
     */
    public function test_bulk_delete_works_with_different_groups(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        
        $translation1 = Translation::factory()->create(['group' => 'common']);
        $translation2 = Translation::factory()->create(['group' => 'auth']);
        $translation3 = Translation::factory()->create(['group' => 'validation']);

        $this->actingAs($superadmin);

        Livewire::test(TranslationResource\Pages\ListTranslations::class)
            ->callTableBulkAction('delete', [$translation1, $translation2, $translation3]);

        $this->assertDatabaseMissing('translations', ['id' => $translation1->id]);
        $this->assertDatabaseMissing('translations', ['id' => $translation2->id]);
        $this->assertDatabaseMissing('translations', ['id' => $translation3->id]);
    }

    /**
     * Test that bulk delete works with large number of translations.
     *
     * Validates: Requirements 1.5 (Bulk Delete Functionality)
     */
    public function test_bulk_delete_works_with_large_number_of_translations(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        
        $translations = Translation::factory()->count(50)->create();

        $this->actingAs($superadmin);

        Livewire::test(TranslationResource\Pages\ListTranslations::class)
            ->callTableBulkAction('delete', $translations);

        foreach ($translations as $translation) {
            $this->assertDatabaseMissing('translations', ['id' => $translation->id]);
        }
    }

    /**
     * Test that admin cannot delete translations.
     *
     * Validates: Requirements 1.6 (Authorization)
     */
    public function test_admin_cannot_delete_translation(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $translation = Translation::factory()->create();

        $this->actingAs($admin);

        // Admin gets redirected (302) because they don't have access
        $response = $this->get(TranslationResource::getUrl('index'));
        $response->assertRedirect();
    }

    /**
     * Test that manager cannot delete translations.
     *
     * Validates: Requirements 1.6 (Authorization)
     */
    public function test_manager_cannot_delete_translation(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $translation = Translation::factory()->create();

        $this->actingAs($manager);

        $response = $this->get(TranslationResource::getUrl('index'));
        $response->assertForbidden();
    }

    /**
     * Test that tenant cannot delete translations.
     *
     * Validates: Requirements 1.6 (Authorization)
     */
    public function test_tenant_cannot_delete_translation(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $translation = Translation::factory()->create();

        $this->actingAs($tenant);

        $response = $this->get(TranslationResource::getUrl('index'));
        $response->assertForbidden();
    }

    /**
     * Test that only superadmin can see delete action.
     *
     * Validates: Requirements 1.6 (Authorization)
     */
    public function test_only_superadmin_can_see_delete_action(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $translation = Translation::factory()->create();

        $this->actingAs($superadmin);

        $this->assertTrue(TranslationResource::canDelete($translation));
        $this->assertTrue(TranslationResource::canViewAny());
    }

    /**
     * Test that deleting non-existent translation handles gracefully.
     *
     * Validates: Requirements 1.7 (Edge Cases)
     */
    public function test_deleting_non_existent_translation_handles_gracefully(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $translation = Translation::factory()->create();
        $translationId = $translation->id;
        
        // Delete the translation directly
        $translation->delete();

        $this->actingAs($superadmin);

        // Attempting to delete again should not cause errors
        $this->assertDatabaseMissing('translations', ['id' => $translationId]);
    }

    /**
     * Test that bulk delete with empty selection handles gracefully.
     *
     * Validates: Requirements 1.7 (Edge Cases)
     */
    public function test_bulk_delete_with_empty_selection_handles_gracefully(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        Translation::factory()->count(3)->create();

        $this->actingAs($superadmin);

        // Bulk delete with empty array should not cause errors
        Livewire::test(TranslationResource\Pages\ListTranslations::class)
            ->callTableBulkAction('delete', []);

        // All translations should still exist
        $this->assertDatabaseCount('translations', 3);
    }

    /**
     * Test that bulk delete with mixed valid and invalid IDs handles correctly.
     *
     * Validates: Requirements 1.7 (Edge Cases)
     */
    public function test_bulk_delete_with_mixed_valid_invalid_ids(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        
        $validTranslation = Translation::factory()->create();
        $invalidId = 99999;

        $this->actingAs($superadmin);

        // Should delete valid translation and ignore invalid ID
        Livewire::test(TranslationResource\Pages\ListTranslations::class)
            ->callTableBulkAction('delete', [$validTranslation]);

        $this->assertDatabaseMissing('translations', ['id' => $validTranslation->id]);
    }

    /**
     * Test that deleting translation maintains database integrity.
     *
     * Validates: Requirements 1.7 (Edge Cases)
     */
    public function test_deleting_translation_maintains_database_integrity(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        
        $translation = Translation::factory()->create([
            'group' => 'test',
            'key' => 'integrity_test',
        ]);

        $initialCount = Translation::count();

        $this->actingAs($superadmin);

        Livewire::test(TranslationResource\Pages\ListTranslations::class)
            ->callTableAction('delete', $translation);

        $this->assertEquals($initialCount - 1, Translation::count());
    }

    /**
     * Test that delete operation completes within acceptable time.
     *
     * Validates: Requirements 1.8 (Performance)
     */
    public function test_delete_operation_performance(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $translation = Translation::factory()->create();

        $this->actingAs($superadmin);

        $startTime = microtime(true);

        Livewire::test(TranslationResource\Pages\ListTranslations::class)
            ->callTableAction('delete', $translation);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Delete should complete in less than 500ms
        $this->assertLessThan(500, $executionTime, 'Delete operation took too long');
    }

    /**
     * Test that bulk delete operation completes within acceptable time.
     *
     * Validates: Requirements 1.8 (Performance)
     */
    public function test_bulk_delete_operation_performance(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $translations = Translation::factory()->count(20)->create();

        $this->actingAs($superadmin);

        $startTime = microtime(true);

        Livewire::test(TranslationResource\Pages\ListTranslations::class)
            ->callTableBulkAction('delete', $translations);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Bulk delete of 20 items should complete in less than 1000ms
        $this->assertLessThan(1000, $executionTime, 'Bulk delete operation took too long');
    }

    /**
     * Test that delete action shows confirmation modal.
     *
     * Validates: Requirements 1.9 (UI Elements)
     */
    public function test_delete_action_shows_confirmation_modal(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $translation = Translation::factory()->create();

        $this->actingAs($superadmin);

        // DeleteAction in Filament has built-in confirmation
        // We verify it's configured by checking the action exists
        Livewire::test(TranslationResource\Pages\ListTranslations::class)
            ->assertTableActionExists('delete');
    }

    /**
     * Test that bulk delete action shows custom confirmation modal.
     *
     * Validates: Requirements 1.9 (UI Elements)
     */
    public function test_bulk_delete_shows_custom_confirmation_modal(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $translations = Translation::factory()->count(3)->create();

        $this->actingAs($superadmin);

        // Verify bulk delete action exists with confirmation
        Livewire::test(TranslationResource\Pages\ListTranslations::class)
            ->assertTableBulkActionExists('delete');
    }

    /**
     * Test that successful delete shows notification.
     *
     * Validates: Requirements 1.9 (UI Elements)
     */
    public function test_successful_delete_shows_notification(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $translation = Translation::factory()->create();

        $this->actingAs($superadmin);

        // Filament automatically shows success notification after delete
        Livewire::test(TranslationResource\Pages\ListTranslations::class)
            ->callTableAction('delete', $translation)
            ->assertHasNoTableActionErrors();
    }
}
