<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Filament\Resources\BuildingResource\RelationManagers\PropertiesRelationManager;
use App\Models\Building;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Integration tests for PropertiesRelationManager.
 *
 * Tests the complete workflow including:
 * - CRUD operations
 * - Tenant management
 * - Authorization
 * - Bulk actions
 */
final class PropertiesRelationManagerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Building $building;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $this->building = Building::factory()->create([
            'tenant_id' => 1,
        ]);

        $this->actingAs($this->admin);
    }

    public function test_complete_property_creation_workflow(): void
    {
        $component = Livewire::test(
            PropertiesRelationManager::class,
            ['ownerRecord' => $this->building]
        );

        $component
            ->callTableAction('create', data: [
                'address' => 'Apartment 42, Floor 5',
                'type' => PropertyType::APARTMENT->value,
                'area_sqm' => 65.5,
            ])
            ->assertHasNoTableActionErrors();

        $property = Property::latest()->first();

        expect($property)
            ->address->toBe('Apartment 42, Floor 5')
            ->type->toBe(PropertyType::APARTMENT)
            ->area_sqm->toBe('65.50')
            ->tenant_id->toBe(1)
            ->building_id->toBe($this->building->id);
    }

    public function test_complete_property_update_workflow(): void
    {
        $property = Property::factory()->create([
            'building_id' => $this->building->id,
            'tenant_id' => 1,
            'address' => 'Old Address',
            'type' => PropertyType::APARTMENT,
            'area_sqm' => 50,
        ]);

        $component = Livewire::test(
            PropertiesRelationManager::class,
            ['ownerRecord' => $this->building]
        );

        $component
            ->callTableAction('edit', $property, data: [
                'address' => 'New Address',
                'type' => PropertyType::HOUSE->value,
                'area_sqm' => 120,
            ])
            ->assertHasNoTableActionErrors();

        $property->refresh();

        expect($property)
            ->address->toBe('New Address')
            ->type->toBe(PropertyType::HOUSE)
            ->area_sqm->toBe('120.00');
    }

    public function test_complete_tenant_assignment_workflow(): void
    {
        $property = Property::factory()->create([
            'building_id' => $this->building->id,
            'tenant_id' => 1,
        ]);

        $tenant = Tenant::factory()->create([
            'tenant_id' => 1,
        ]);

        $component = Livewire::test(
            PropertiesRelationManager::class,
            ['ownerRecord' => $this->building]
        );

        $component
            ->callTableAction('manage_tenant', $property, data: ['tenant_id' => $tenant->id])
            ->assertHasNoTableActionErrors();

        expect($property->fresh()->tenants->first()->id)->toBe($tenant->id);
    }

    public function test_complete_tenant_removal_workflow(): void
    {
        $property = Property::factory()->create([
            'building_id' => $this->building->id,
            'tenant_id' => 1,
        ]);

        $tenant = Tenant::factory()->create([
            'tenant_id' => 1,
        ]);

        $property->tenants()->attach($tenant->id);

        $component = Livewire::test(
            PropertiesRelationManager::class,
            ['ownerRecord' => $this->building]
        );

        $component
            ->callTableAction('manage_tenant', $property, data: ['tenant_id' => null]);

        expect($property->fresh()->tenants)->toBeEmpty();
    }

    public function test_bulk_delete_workflow(): void
    {
        $properties = Property::factory()->count(3)->create([
            'building_id' => $this->building->id,
            'tenant_id' => 1,
        ]);

        $component = Livewire::test(
            PropertiesRelationManager::class,
            ['ownerRecord' => $this->building]
        );

        $component
            ->callTableBulkAction('delete', $properties)
            ->assertHasNoTableActionErrors();

        expect(Property::count())->toBe(0);
    }

    public function test_filters_work_correctly(): void
    {
        Property::factory()->create([
            'building_id' => $this->building->id,
            'tenant_id' => 1,
            'type' => PropertyType::APARTMENT,
            'area_sqm' => 50,
        ]);

        Property::factory()->create([
            'building_id' => $this->building->id,
            'tenant_id' => 1,
            'type' => PropertyType::HOUSE,
            'area_sqm' => 150,
        ]);

        $component = Livewire::test(
            PropertiesRelationManager::class,
            ['ownerRecord' => $this->building]
        );

        // Filter by apartment type
        $component
            ->filterTable('type', PropertyType::APARTMENT->value)
            ->assertCanSeeTableRecords(Property::where('type', PropertyType::APARTMENT)->get())
            ->assertCanNotSeeTableRecords(Property::where('type', PropertyType::HOUSE)->get());

        // Filter by large properties
        $component
            ->resetTableFilters()
            ->filterTable('large_properties', true)
            ->assertCanSeeTableRecords(Property::where('area_sqm', '>', 100)->get())
            ->assertCanNotSeeTableRecords(Property::where('area_sqm', '<=', 100)->get());
    }

    public function test_search_works_correctly(): void
    {
        Property::factory()->create([
            'building_id' => $this->building->id,
            'tenant_id' => 1,
            'address' => 'Apartment 101',
        ]);

        Property::factory()->create([
            'building_id' => $this->building->id,
            'tenant_id' => 1,
            'address' => 'House 202',
        ]);

        $component = Livewire::test(
            PropertiesRelationManager::class,
            ['ownerRecord' => $this->building]
        );

        $component
            ->searchTable('Apartment')
            ->assertCanSeeTableRecords(Property::where('address', 'like', '%Apartment%')->get())
            ->assertCanNotSeeTableRecords(Property::where('address', 'like', '%House%')->get());
    }

    public function test_authorization_prevents_unauthorized_access(): void
    {
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
        ]);

        $this->actingAs($tenant);

        $component = Livewire::test(
            PropertiesRelationManager::class,
            ['ownerRecord' => $this->building]
        );

        $component
            ->assertForbidden()
            ->assertDontSeeLivewire(PropertiesRelationManager::class);
    }
}
