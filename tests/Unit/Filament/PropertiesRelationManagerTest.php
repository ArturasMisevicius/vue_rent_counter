<?php

declare(strict_types=1);

namespace Tests\Unit\Filament;

use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Filament\Resources\BuildingResource\RelationManagers\PropertiesRelationManager;
use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use ReflectionProperty;
use Tests\TestCase;

final class PropertiesRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    private PropertiesRelationManager $manager;

    private Building $building;

    protected function setUp(): void
    {
        parent::setUp();

        $this->building = Building::factory()->create(['tenant_id' => 1]);
        $this->manager = new PropertiesRelationManager;
        $this->manager->ownerRecord = $this->building;

        $this->actingAs(User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]));
    }

    public function test_form_schema_contains_expected_fields(): void
    {
        $schema = $this->manager->form(Schema::make());
        $this->assertNotEmpty($schema->getComponents());

        $reflection = new ReflectionClass($this->manager);
        $address = $reflection->getMethod('getAddressField');
        $type = $reflection->getMethod('getTypeField');
        $area = $reflection->getMethod('getAreaField');

        $address->setAccessible(true);
        $type->setAccessible(true);
        $area->setAccessible(true);

        $fields = [
            $address->invoke($this->manager),
            $type->invoke($this->manager),
            $area->invoke($this->manager),
        ];

        $names = array_map(fn ($field) => $field->getName(), $fields);

        $this->assertContains('address', $names);
        $this->assertContains('type', $names);
        $this->assertContains('area_sqm', $names);
    }

    public function test_prepare_property_data_scopes_to_owner_and_user(): void
    {
        $reflection = new ReflectionClass($this->manager);
        $method = $reflection->getMethod('preparePropertyData');
        $method->setAccessible(true);

        $result = $method->invoke($this->manager, [
            'address' => 'Unit 10',
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => 80,
            'ignored' => 'nope',
        ]);

        $this->assertSame($this->building->id, $result['building_id']);
        $this->assertSame(auth()->user()->tenant_id, $result['tenant_id']);
        $this->assertArrayNotHasKey('ignored', $result);
    }

    public function test_table_modify_query_adds_relationship_eager_loading(): void
    {
        $table = $this->manager->table(\Filament\Tables\Table::make($this->manager));

        $scopes = new ReflectionProperty($table, 'queryScopes');
        $scopes->setAccessible(true);

        $query = Property::query();

        foreach ($scopes->getValue($table) as $scope) {
            $query = $scope($query) ?? $query;
        }

        $this->assertArrayHasKey('tenants', $query->getEagerLoads());
        $this->assertArrayHasKey('meters', $query->getEagerLoads());
    }
}
