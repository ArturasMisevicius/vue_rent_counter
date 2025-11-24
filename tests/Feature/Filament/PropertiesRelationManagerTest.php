<?php

declare(strict_types=1);

use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Filament\Resources\BuildingResource\RelationManagers\PropertiesRelationManager;
use App\Models\Building;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    $this->building = Building::factory()->create([
        'tenant_id' => 1,
    ]);

    $this->manager = new PropertiesRelationManager;
    $this->manager->ownerRecord = $this->building;

    $this->actingAs($this->admin);
});

test('form fields use translated labels and validation wiring', function () {
    $form = $this->manager->form(Schema::make());

    expect($form->getComponents())->not->toBeEmpty();

    $reflection = new ReflectionClass($this->manager);
    $address = $reflection->getMethod('getAddressField');
    $address->setAccessible(true);

    /** @var TextInput $addressField */
    $addressField = $address->invoke($this->manager);

    expect($addressField)->toBeInstanceOf(TextInput::class)
        ->and($addressField->getLabel())->toBe(__('properties.labels.address'))
        ->and(Lang::has('properties.validation.address.required'))->toBeTrue();
});

test('default area comes from billing config by property type', function () {
    config([
        'billing.property.default_apartment_area' => 66,
        'billing.property.default_house_area' => 144,
    ]);

    $reflection = new ReflectionClass($this->manager);
    $method = $reflection->getMethod('setDefaultArea');
    $method->setAccessible(true);

    $setter = function ($key, $value) {
        $this->captured = $value;
    };

    $method->invoke($this->manager, PropertyType::APARTMENT->value, $setter->bindTo($this));
    expect($this->captured)->toBe(66);

    $method->invoke($this->manager, PropertyType::HOUSE->value, $setter->bindTo($this));
    expect($this->captured)->toBe(144);
});

test('preparePropertyData injects tenant/building ids and strips extras', function () {
    $reflection = new ReflectionClass($this->manager);
    $method = $reflection->getMethod('preparePropertyData');
    $method->setAccessible(true);

    $result = $method->invoke($this->manager, [
        'address' => 'Test Address',
        'type' => PropertyType::HOUSE->value,
        'area_sqm' => 120,
        'unexpected' => 'ignore-me',
    ]);

    expect($result)->toMatchArray([
        'address' => 'Test Address',
        'type' => PropertyType::HOUSE->value,
        'area_sqm' => 120,
        'tenant_id' => $this->admin->tenant_id,
        'building_id' => $this->building->id,
    ])->and($result)->not->toHaveKey('unexpected');
});

test('table eager loads tenants and meters to avoid N+1s', function () {
    $table = $this->manager->table(\Filament\Tables\Table::make($this->manager));

    $scopes = (new ReflectionProperty($table, 'queryScopes'));
    $scopes->setAccessible(true);

    $query = Property::query();

    foreach ($scopes->getValue($table) as $scope) {
        $query = $scope($query) ?? $query;
    }

    $eagerLoads = array_keys($query->getEagerLoads());

    expect($eagerLoads)->toContain('tenants', 'meters');
});

test('tenant management form hides already assigned tenants', function () {
    $property = Property::factory()->create([
        'tenant_id' => 1,
        'building_id' => $this->building->id,
    ]);

    // Active tenant should be excluded from options
    $activeTenant = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'lease_end' => now()->addMonth(),
    ]);

    // Vacated tenant should be available for selection
    $availableTenant = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'lease_end' => now()->subMonth(),
    ]);

    DB::table('property_tenant')
        ->where('property_id', $property->id)
        ->where('tenant_id', $availableTenant->id)
        ->update(['vacated_at' => now()->subDay()]);

    $method = new ReflectionMethod($this->manager, 'getTenantManagementForm');
    $method->setAccessible(true);

    /** @var Select $select */
    $select = $method->invoke($this->manager, $property)[0];
    $options = $select->getOptions();

    expect($select)->toBeInstanceOf(Select::class)
        ->and($options)->toHaveKey($availableTenant->id)
        ->and($options)->not->toHaveKey($activeTenant->id);
});

test('handleTenantManagement updates pivot assignment lifecycle', function () {
    $property = Property::factory()->create([
        'tenant_id' => 1,
        'building_id' => $this->building->id,
    ]);

    $tenant = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'lease_end' => now()->subMonths(2), // start as vacated
    ]);

    DB::table('property_tenant')
        ->where('property_id', $property->id)
        ->where('tenant_id', $tenant->id)
        ->update(['vacated_at' => now()->subMonth()]);

    $method = new ReflectionMethod($this->manager, 'handleTenantManagement');
    $method->setAccessible(true);

    // Assign tenant
    $method->invoke($this->manager, $property, ['tenant_id' => $tenant->id]);

    $pivot = DB::table('property_tenant')
        ->where('property_id', $property->id)
        ->where('tenant_id', $tenant->id)
        ->latest('updated_at')
        ->first();

    expect($pivot->vacated_at)->toBeNull();

    // Vacate tenant
    $method->invoke($this->manager, $property, ['tenant_id' => null]);

    $pivotAfter = DB::table('property_tenant')
        ->where('property_id', $property->id)
        ->where('tenant_id', $tenant->id)
        ->latest('updated_at')
        ->first();

    expect($pivotAfter->vacated_at)->not->toBeNull();
});

test('applyTenantScoping returns original query for relation', function () {
    $method = new ReflectionMethod($this->manager, 'applyTenantScoping');
    $method->setAccessible(true);

    $query = Property::query();

    expect($method->invoke($this->manager, $query))->toBe($query);
});

test('canViewForRecord relies on policy', function () {
    $canView = PropertiesRelationManager::canViewForRecord($this->building, 'view');

    expect($canView)->toBeTrue();
});
