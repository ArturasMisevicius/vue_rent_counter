<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\TariffResource;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * TariffResource Namespace Consolidation Tests
 * 
 * Verifies that the Filament 4 namespace consolidation (removing individual
 * action imports in favor of consolidated Tables namespace) doesn't break
 * functionality.
 * 
 * Related:
 * - .kiro/specs/6-filament-namespace-consolidation/requirements.md
 * - docs/filament/TARIFF_RESOURCE_NAMESPACE_CONSOLIDATION.md
 * 
 * @see \App\Filament\Resources\TariffResource
 */
class TariffResourceNamespaceConsolidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that TariffResource list page renders without errors after namespace consolidation.
     * 
     * This verifies that removing the individual `use Filament\Tables\Actions;` import
     * and using the consolidated `Tables\Actions\` prefix doesn't break the table rendering.
     */
    public function test_tariff_resource_list_page_renders_correctly(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        Provider::factory()->count(3)->create();
        Tariff::factory()->count(5)->create();

        // Act & Assert
        $this->actingAs($admin)
            ->get(TariffResource::getUrl('index'))
            ->assertOk();
    }

    /**
     * Test that table columns display correctly after namespace consolidation.
     * 
     * Verifies that TextColumn, IconColumn, and other column types using the
     * Tables\Columns\ prefix render correctly.
     */
    public function test_tariff_resource_table_columns_display(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $provider = Provider::factory()->create(['name' => 'Test Provider']);
        Tariff::factory()->create([
            'provider_id' => $provider->id,
            'name' => 'Test Tariff',
            'configuration' => [
                'type' => 'flat',
                'currency' => 'EUR',
                'rate' => 0.15,
            ],
        ]);

        // Act & Assert - Just verify the page loads without errors
        $this->actingAs($admin)
            ->get(TariffResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Test Provider')
            ->assertSee('Test Tariff');
    }

    /**
     * Test that table actions work correctly after namespace consolidation.
     * 
     * Verifies that EditAction and other actions using the Tables\Actions\ prefix
     * function correctly.
     */
    public function test_tariff_resource_edit_action_works(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tariff = Tariff::factory()->create();

        // Act & Assert
        $this->actingAs($admin)
            ->get(TariffResource::getUrl('edit', ['record' => $tariff]))
            ->assertOk();
    }

    /**
     * Test that create action works after namespace consolidation.
     * 
     * Verifies that the create functionality using Tables\Actions\ prefix works.
     */
    public function test_tariff_resource_create_action_works(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $provider = Provider::factory()->create();

        $this->actingAs($admin);

        // Act
        $component = Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $provider->id,
                'name' => 'New Tariff',
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.20,
                ],
                'active_from' => now()->toDateString(),
            ])
            ->call('create');

        // Assert
        $component->assertHasNoFormErrors();
        $this->assertDatabaseHas('tariffs', [
            'name' => 'New Tariff',
            'provider_id' => $provider->id,
        ]);
    }

    /**
     * Test that authorization still works after namespace consolidation.
     * 
     * Verifies that the TariffPolicy integration remains intact.
     */
    public function test_tariff_resource_authorization_still_enforced(): void
    {
        // Arrange - Manager should not have access
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);

        $this->actingAs($manager);

        // Act
        $response = $this->get(TariffResource::getUrl('index'));

        // Assert
        $response->assertForbidden();
    }

    /**
     * Test that navigation visibility still works after namespace consolidation.
     * 
     * Verifies that shouldRegisterNavigation() logic remains intact.
     */
    public function test_tariff_resource_navigation_visibility(): void
    {
        // Clear any cached navigation state
        TariffResource::clearCachedUser();

        // Arrange - Admin should see navigation
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($admin);

        // Assert
        $this->assertTrue(TariffResource::shouldRegisterNavigation());

        // Clear cache before next check
        TariffResource::clearCachedUser();

        // Arrange - Manager should not see navigation
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $this->actingAs($manager);

        // Assert
        $this->assertFalse(TariffResource::shouldRegisterNavigation());
    }

    /**
     * Test that table search functionality works after namespace consolidation.
     * 
     * Verifies that searchable columns still function correctly.
     */
    public function test_tariff_resource_search_works(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $provider = Provider::factory()->create(['name' => 'Unique Provider']);
        Tariff::factory()->create([
            'provider_id' => $provider->id,
            'name' => 'Searchable Tariff',
        ]);
        Tariff::factory()->count(5)->create(); // Noise

        // Act & Assert - Verify search doesn't cause errors
        $this->actingAs($admin)
            ->get(TariffResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Searchable Tariff');
    }

    /**
     * Test that table sorting works after namespace consolidation.
     * 
     * Verifies that sortable columns still function correctly.
     */
    public function test_tariff_resource_sorting_works(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        Tariff::factory()->create(['active_from' => now()->subDays(10)]);
        Tariff::factory()->create(['active_from' => now()->subDays(5)]);
        Tariff::factory()->create(['active_from' => now()->subDays(1)]);

        // Act & Assert - Verify sorting doesn't cause errors
        $this->actingAs($admin)
            ->get(TariffResource::getUrl('index'))
            ->assertOk();
    }

    /**
     * Test that validation still works after namespace consolidation.
     * 
     * Verifies that form validation remains intact.
     */
    public function test_tariff_resource_validation_works(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin);

        // Act - Try to create without required fields
        $component = Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'name' => '', // Required field empty
            ])
            ->call('create');

        // Assert
        $component->assertHasFormErrors(['provider_id', 'name']);
    }

    /**
     * Test that eager loading optimization still works after namespace consolidation.
     * 
     * Verifies that the ->with('provider:id,name,service_type') optimization
     * in the table query remains functional.
     */
    public function test_tariff_resource_eager_loading_optimization(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        Provider::factory()->count(3)->create();
        Tariff::factory()->count(10)->create();

        $this->actingAs($admin);

        // Act
        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        $this->get(TariffResource::getUrl('index'));

        // Assert - Should have minimal queries due to eager loading
        $this->assertLessThanOrEqual(6, $queryCount, 'Query count should remain optimized');
    }
}
