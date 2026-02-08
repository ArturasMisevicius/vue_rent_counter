<?php

use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;

test('manager can view properties list', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);

    Property::factory()->count(3)->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAs($manager)->get(route('manager.properties.index'));

    $response->assertOk();
    $response->assertViewIs('manager.properties.index');
    $response->assertViewHas('properties');
});

test('manager can view property details', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);

    $property = Property::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAs($manager)->get(route('manager.properties.show', $property));

    $response->assertOk();
    $response->assertViewIs('manager.properties.show');
    $response->assertSee($property->address);
});

test('manager can view create property form', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);

    $response = $this->actingAs($manager)->get(route('manager.properties.create'));

    $response->assertOk();
    $response->assertViewIs('manager.properties.create');
    $response->assertSee('Create Property');
});

test('manager can create property', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);

    $building = Building::factory()->create(['tenant_id' => $tenant->id]);

    $propertyData = [
        'address' => '123 Test Street, Vilnius',
        'type' => PropertyType::APARTMENT->value,
        'area_sqm' => 75.50,
        'building_id' => $building->id,
    ];

    $response = $this->actingAs($manager)->post(route('manager.properties.store'), $propertyData);

    $response->assertRedirect();
    $this->assertDatabaseHas('properties', [
        'address' => '123 Test Street, Vilnius',
        'type' => PropertyType::APARTMENT->value,
        'tenant_id' => $tenant->id,
    ]);
});

test('manager can view edit property form', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);

    $property = Property::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAs($manager)->get(route('manager.properties.edit', $property));

    $response->assertOk();
    $response->assertViewIs('manager.properties.edit');
    $response->assertSee($property->address);
});

test('manager can update property', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);

    $property = Property::factory()->create(['tenant_id' => $tenant->id]);

    $updatedData = [
        'address' => '456 Updated Street, Vilnius',
        'type' => PropertyType::HOUSE->value,
        'area_sqm' => 120.00,
        'building_id' => null,
    ];

    $response = $this->actingAs($manager)->put(route('manager.properties.update', $property), $updatedData);

    $response->assertRedirect();
    $this->assertDatabaseHas('properties', [
        'id' => $property->id,
        'address' => '456 Updated Street, Vilnius',
        'type' => PropertyType::HOUSE->value,
    ]);
});

test('manager cannot delete property', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);

    $property = Property::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAs($manager)->delete(route('manager.properties.destroy', $property));

    $response->assertForbidden();
    $this->assertDatabaseHas('properties', ['id' => $property->id]);
});

test('manager cannot view properties from other tenants', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant1->id,
    ]);

    $property1 = Property::factory()->create(['tenant_id' => $tenant1->id]);
    $property2 = Property::factory()->create(['tenant_id' => $tenant2->id]);

    $response = $this->actingAs($manager)->get(route('manager.properties.index'));

    $response->assertOk();
    $response->assertSee($property1->address);
    $response->assertDontSee($property2->address);
});

test('property type validation rejects invalid types', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);

    $propertyData = [
        'address' => '123 Test Street, Vilnius',
        'type' => 'invalid_type',
        'area_sqm' => 75.50,
        'building_id' => null,
    ];

    $response = $this->actingAs($manager)->post(route('manager.properties.store'), $propertyData);

    $response->assertSessionHasErrors('type');
});

test('property area validation rejects negative values', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);

    $propertyData = [
        'address' => '123 Test Street, Vilnius',
        'type' => PropertyType::APARTMENT->value,
        'area_sqm' => -10,
        'building_id' => null,
    ];

    $response = $this->actingAs($manager)->post(route('manager.properties.store'), $propertyData);

    $response->assertSessionHasErrors('area_sqm');
});
