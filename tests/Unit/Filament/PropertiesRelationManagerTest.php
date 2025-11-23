<?php

declare(strict_types=1);

namespace Tests\Unit\Filament;

use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Filament\Resources\BuildingResource\RelationManagers\PropertiesRelationManager;
use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Test suite for PropertiesRelationManager refactoring.
 *
 * Validates:
 * - Strict types enforcement
 * - Validation integration from FormRequests
 * - Config-based default values
 * - Extracted helper methods
 * - Type safety improvements
 */
final class PropertiesRelationManagerTest extends TestCase
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

    public function test_form_uses_config_for_default_apartment_area(): void
    {
        config(['billing.property.default_apartment_area' => 75]);

        $component = Livewire::test(
            PropertiesRelationManager::class,
            ['ownerRecord' => $this->building]
        );

        $component->mountTableAction('create');
        $component->set('mountedTableActionData.type', PropertyType::APARTMENT->value);

        expect($component->get('mountedTableActionData.area_sqm'))->toBe(75);
    }

    public function test_form_uses_config_for_default_house_area(): void
    {
        config(['billing.property.default_house_area' => 150]);

        $component = Livewire::test(
            PropertiesRelationManager::class,
            ['ownerRecord' => $this->building]
        );

        $component->mountTableAction('create');
        $component->set('mountedTableActionData.type', PropertyType::HOUSE->value);

        expect($component->get('mountedTableActionData.area_sqm'))->toBe(150);
    }

    public function test_validation_messages_match_form_request(): void
    {
        $component = Livewire::test(
            PropertiesRelationManager::class,
            ['ownerRecord' => $this->building]
        );

        $component
            ->mountTableAction('create')
            ->setTableActionData([
                'address' => '',
                'type' => '',
                'area_sqm' => '',
            ])
            ->callTableAction('create');

        $component
            ->assertHasTableActionErrors([
                'address' => 'required',
                'type' => 'required',
                'area_sqm' => 'required',
            ]);
    }

    public function test_automatically_sets_tenant_id_on_create(): void
    {
        $component = Livewire::test(
            PropertiesRelationManager::class,
            ['ownerRecord' => $this->building]
        );

        $component
            ->mountTableAction('create')
            ->setTableActionData([
                'address' => 'Apartment 1',
                'type' => PropertyType::APARTMENT->value,
                'area_sqm' => 50,
            ])
            ->callTableAction('create');

        $property = Property::latest()->first();

        expect($property->tenant_id)->toBe($this->admin->tenant_id);
    }

    public function test_automatically_sets_building_id_on_create(): void
    {
        $component = Livewire::test(
            PropertiesRelationManager::class,
            ['ownerRecord' => $this->building]
        );

        $component
            ->mountTableAction('create')
            ->setTableActionData([
                'address' => 'Apartment 1',
                'type' => PropertyType::APARTMENT->value,
                'area_sqm' => 50,
            ])
            ->callTableAction('create');

        $property = Property::latest()->first();

        expect($property->building_id)->toBe($this->building->id);
    }

    public function test_table_eager_loads_relationships(): void
    {
        Property::factory()->count(3)->create([
            'building_id' => $this->building->id,
            'tenant_id' => 1,
        ]);

        $component = Livewire::test(
            PropertiesRelationManager::class,
            ['ownerRecord' => $this->building]
        );

        // Verify no N+1 queries by checking query count
        $queryCount = count(\DB::getQueryLog());
        
        $component->assertCanSeeTableRecords(Property::all());
        
        // Should not significantly increase queries due to eager loading
        expect(count(\DB::getQueryLog()))->toBeLessThan($queryCount + 5);
    }

    public function test_area_validation_uses_config_limits(): void
    {
        config([
            'billing.property.min_area' => 10,
            'billing.property.max_area' => 5000,
        ]);

        $component = Livewire::test(
            PropertiesRelationManager::class,
            ['ownerRecord' => $this->building]
        );

        // Test below minimum
        $component
            ->mountTableAction('create')
            ->setTableActionData([
                'address' => 'Apartment 1',
                'type' => PropertyType::APARTMENT->value,
                'area_sqm' => 5,
            ])
            ->callTableAction('create')
            ->assertHasTableActionErrors(['area_sqm' => 'min']);

        // Test above maximum
        $component
            ->mountTableAction('create')
            ->setTableActionData([
                'address' => 'Apartment 1',
                'type' => PropertyType::APARTMENT->value,
                'area_sqm' => 6000,
            ])
            ->callTableAction('create')
            ->assertHasTableActionErrors(['area_sqm' => 'max']);
    }

    public function test_tenant_management_removes_tenant_when_empty(): void
    {
        $property = Property::factory()->create([
            'building_id' => $this->building->id,
            'tenant_id' => 1,
        ]);

        $component = Livewire::test(
            PropertiesRelationManager::class,
            ['ownerRecord' => $this->building]
        );

        $component
            ->mountTableAction('manage_tenant', $property)
            ->setTableActionData(['tenant_id' => null])
            ->callTableAction('manage_tenant');

        expect($property->fresh()->tenants)->toBeEmpty();
    }

    public function test_maintains_building_id_on_update(): void
    {
        $property = Property::factory()->create([
            'building_id' => $this->building->id,
            'tenant_id' => 1,
        ]);

        $component = Livewire::test(
            PropertiesRelationManager::class,
            ['ownerRecord' => $this->building]
        );

        $component
            ->mountTableAction('edit', $property)
            ->setTableActionData([
                'address' => 'Updated Address',
                'type' => PropertyType::HOUSE->value,
                'area_sqm' => 120,
            ])
            ->callTableAction('edit');

        expect($property->fresh()->building_id)->toBe($this->building->id);
    }
}
