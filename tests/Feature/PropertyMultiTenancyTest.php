<?php

use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Models\Property;
use App\Models\User;

/**
 * Property Multi-Tenancy Isolation Tests
 * 
 * Tests that managers can only access properties from their own tenant,
 * and that cross-tenant property access is properly prevented.
 * 
 * Requirements: 4.1, 4.2
 */

test('manager from tenant 1 sees only tenant 1 properties', function () {
    // Create manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
    ]);
    
    // Set session tenant_id
    session(['tenant_id' => 1]);

    // Create properties for tenant 1
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.00,
    ]);
    
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 2',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 60.00,
    ]);

    // Create properties for tenant 2
    Property::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'address' => 'Pilies g. 22, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 55.00,
    ]);
    
    Property::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'address' => 'Pilies g. 22, Apt 2',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 65.00,
    ]);

    // Access property index as manager from tenant 1
    $response = $this->actingAs($manager1)->get('/manager/properties');

    // Assert successful access
    $response->assertOk();
    
    // Assert only tenant 1 properties are visible
    $response->assertSee('Gedimino pr. 15, Apt 1');
    $response->assertSee('Gedimino pr. 15, Apt 2');
    
    // Assert tenant 2 properties are not visible
    $response->assertDontSee('Pilies g. 22, Apt 1');
    $response->assertDontSee('Pilies g. 22, Apt 2');
});

test('manager from tenant 1 cannot access tenant 2 property', function () {
    // Create manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
    ]);
    
    // Set session tenant_id to 1
    session(['tenant_id' => 1]);

    // Create property for tenant 2
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'address' => 'Pilies g. 22, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 55.00,
    ]);

    // Attempt to access tenant 2 property as manager from tenant 1
    $response = $this->actingAs($manager1)->get("/manager/properties/{$property2->id}");

    // Assert 404 error (not 403, to avoid information disclosure)
    $response->assertNotFound();
});

test('property queries automatically filter by tenant_id', function () {
    // Set session tenant_id to 1
    session(['tenant_id' => 1]);

    // Create properties for tenant 1
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.00,
    ]);
    
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 2',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 60.00,
    ]);

    // Create properties for tenant 2
    Property::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'address' => 'Pilies g. 22, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 55.00,
    ]);
    
    Property::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'address' => 'Pilies g. 22, Apt 2',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 65.00,
    ]);

    // Query properties (should automatically filter by tenant_id from session)
    $properties = Property::all();

    // Assert only tenant 1 properties are returned
    expect($properties)->toHaveCount(2);
    expect($properties->pluck('id')->toArray())->toContain($property1->id);
    expect($properties->pluck('id')->toArray())->toContain($property2->id);
});

test('manager from tenant 2 sees only tenant 2 properties', function () {
    // Create manager for tenant 2
    $manager2 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 2,
    ]);
    
    // Set session tenant_id
    session(['tenant_id' => 2]);

    // Create properties for tenant 1
    Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.00,
    ]);
    
    Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 2',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 60.00,
    ]);

    // Create properties for tenant 2
    $property3 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'address' => 'Pilies g. 22, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 55.00,
    ]);
    
    $property4 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'address' => 'Pilies g. 22, Apt 2',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 65.00,
    ]);

    // Access property index as manager from tenant 2
    $response = $this->actingAs($manager2)->get('/manager/properties');

    // Assert successful access
    $response->assertOk();
    
    // Assert only tenant 2 properties are visible
    $response->assertSee('Pilies g. 22, Apt 1');
    $response->assertSee('Pilies g. 22, Apt 2');
    
    // Assert tenant 1 properties are not visible
    $response->assertDontSee('Gedimino pr. 15, Apt 1');
    $response->assertDontSee('Gedimino pr. 15, Apt 2');
});

test('manager from tenant 2 cannot access tenant 1 property', function () {
    // Create manager for tenant 2
    $manager2 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 2,
    ]);
    
    // Set session tenant_id to 2
    session(['tenant_id' => 2]);

    // Create property for tenant 1
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.00,
    ]);

    // Attempt to access tenant 1 property as manager from tenant 2
    $response = $this->actingAs($manager2)->get("/manager/properties/{$property1->id}");

    // Assert 404 error (not 403, to avoid information disclosure)
    $response->assertNotFound();
});

test('manager cannot edit property from another tenant', function () {
    // Create manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
    ]);
    
    // Set session tenant_id to 1
    session(['tenant_id' => 1]);

    // Create property for tenant 2
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'address' => 'Pilies g. 22, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 55.00,
    ]);

    // Attempt to access edit page for tenant 2 property
    $response = $this->actingAs($manager1)->get("/manager/properties/{$property2->id}/edit");

    // Assert 404 error
    $response->assertNotFound();
});

test('manager cannot update property from another tenant', function () {
    // Create manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
    ]);
    
    // Set session tenant_id to 1
    session(['tenant_id' => 1]);

    // Create property for tenant 2
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'address' => 'Pilies g. 22, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 55.00,
    ]);

    // Attempt to update tenant 2 property
    $response = $this->actingAs($manager1)->put("/manager/properties/{$property2->id}", [
        'address' => 'Updated Address',
        'type' => PropertyType::APARTMENT->value,
        'area_sqm' => 60.00,
    ]);

    // Assert 404 error
    $response->assertNotFound();
    
    // Assert property was not updated
    $this->assertDatabaseHas('properties', [
        'id' => $property2->id,
        'address' => 'Pilies g. 22, Apt 1',
        'area_sqm' => 55.00,
    ]);
});

test('manager cannot delete property from another tenant', function () {
    // Create manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
    ]);
    
    // Set session tenant_id to 1
    session(['tenant_id' => 1]);

    // Create property for tenant 2
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'address' => 'Pilies g. 22, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 55.00,
    ]);

    // Attempt to delete tenant 2 property
    $response = $this->actingAs($manager1)->delete("/manager/properties/{$property2->id}");

    // Assert 404 error
    $response->assertNotFound();
    
    // Assert property still exists
    $this->assertDatabaseHas('properties', [
        'id' => $property2->id,
        'address' => 'Pilies g. 22, Apt 1',
    ]);
});

test('changing session tenant_id changes visible properties', function () {
    // Create properties for tenant 1
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.00,
    ]);

    // Create properties for tenant 2
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'address' => 'Pilies g. 22, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 55.00,
    ]);

    // Set session to tenant 1
    session(['tenant_id' => 1]);
    
    // Query should return only tenant 1 properties
    $properties = Property::all();
    expect($properties)->toHaveCount(1);
    expect($properties->first()->id)->toBe($property1->id);

    // Change session to tenant 2
    session(['tenant_id' => 2]);
    
    // Query should now return only tenant 2 properties
    $properties = Property::all();
    expect($properties)->toHaveCount(1);
    expect($properties->first()->id)->toBe($property2->id);
});

test('property count reflects only tenant-scoped properties', function () {
    // Set session tenant_id to 1
    session(['tenant_id' => 1]);

    // Create 3 properties for tenant 1
    Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.00,
    ]);
    
    Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 2',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 60.00,
    ]);
    
    Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 3',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 55.00,
    ]);

    // Create 2 properties for tenant 2
    Property::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'address' => 'Pilies g. 22, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 55.00,
    ]);
    
    Property::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'address' => 'Pilies g. 22, Apt 2',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 65.00,
    ]);

    // Count should only include tenant 1 properties
    $count = Property::count();
    expect($count)->toBe(3);
});

test('property find returns null for cross-tenant property', function () {
    // Set session tenant_id to 1
    session(['tenant_id' => 1]);

    // Create property for tenant 2
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'address' => 'Pilies g. 22, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 55.00,
    ]);

    // Attempt to find tenant 2 property while scoped to tenant 1
    $foundProperty = Property::find($property2->id);

    // Should return null due to tenant scope
    expect($foundProperty)->toBeNull();
});

test('property exists check respects tenant scope', function () {
    // Set session tenant_id to 1
    session(['tenant_id' => 1]);

    // Create property for tenant 2
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'address' => 'Pilies g. 22, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 55.00,
    ]);

    // Check if property exists (should return false due to tenant scope)
    $exists = Property::where('id', $property2->id)->exists();

    // Should return false
    expect($exists)->toBeFalse();
});
