<?php

use App\Enums\UserRole;
use App\Enums\SubscriptionStatus;
use App\Models\Building;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Feature: hierarchical-user-management, Property 9: Subscription status affects access
// Validates: Requirements 3.4
test('admin with expired subscription can perform read operations', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create admin with expired subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);
    
    // Create expired subscription
    Subscription::factory()->expired()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::EXPIRED->value,
        'expires_at' => now()->subDays(fake()->numberBetween(1, 30)),
    ]);
    
    // Create some properties for the admin to read
    $propertyCount = fake()->numberBetween(1, 5);
    for ($i = 0; $i < $propertyCount; $i++) {
        Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'address' => fake()->address(),
            'type' => fake()->randomElement(['apartment', 'house']),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
        ]);
    }
    
    // Create some buildings for the admin to read
    $buildingCount = fake()->numberBetween(1, 3);
    for ($i = 0; $i < $buildingCount; $i++) {
        Building::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'name' => fake()->company(),
            'address' => fake()->address(),
            'total_apartments' => fake()->numberBetween(1, 100),
        ]);
    }
    
    // Act as admin with expired subscription
    $this->actingAs($admin);
    
    // Property: Admin with expired subscription should be able to perform read operations
    // Test reading properties
    $response = $this->get(route('manager.properties.index'));
    expect($response->status())->toBe(200);
    
    // Verify warning message about expired subscription
    expect(session('warning'))->toContain('subscription has expired');
    
    // Test reading buildings
    $response = $this->get(route('manager.buildings.index'));
    expect($response->status())->toBe(200);
    
    // Verify data is accessible via models
    $properties = Property::all();
    expect($properties)->toHaveCount($propertyCount);
    
    $buildings = Building::all();
    expect($buildings)->toHaveCount($buildingCount);
})->repeat(100);

// Feature: hierarchical-user-management, Property 9: Subscription status affects access
// Validates: Requirements 3.4
test('admin with expired subscription cannot perform write operations on properties', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create admin with expired subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);
    
    // Create expired subscription
    Subscription::factory()->expired()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::EXPIRED->value,
        'expires_at' => now()->subDays(fake()->numberBetween(1, 30)),
    ]);
    
    // Act as admin with expired subscription
    $this->actingAs($admin);
    
    // Property: Admin with expired subscription should NOT be able to create properties
    $response = $this->post(route('manager.properties.store'), [
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Should be redirected (302) with error message, not successful (200 or 201)
    expect($response->status())->toBe(302)
        ->and($response->headers->get('Location'))->toContain('dashboard');
    
    // Verify error message is in session
    expect(session('error'))->toContain('subscription has expired');
})->repeat(100);

// Feature: hierarchical-user-management, Property 9: Subscription status affects access
// Validates: Requirements 3.4
test('admin with expired subscription cannot perform write operations on buildings', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create admin with expired subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);
    
    // Create expired subscription
    Subscription::factory()->expired()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::EXPIRED->value,
        'expires_at' => now()->subDays(fake()->numberBetween(1, 30)),
    ]);
    
    // Act as admin with expired subscription
    $this->actingAs($admin);
    
    // Property: Admin with expired subscription should NOT be able to create buildings
    $response = $this->post(route('manager.buildings.store'), [
        'name' => fake()->company(),
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(1, 100),
    ]);
    
    // Should be redirected (302) with error message, not successful (200 or 201)
    expect($response->status())->toBe(302)
        ->and($response->headers->get('Location'))->toContain('dashboard');
    
    // Verify error message is in session
    expect(session('error'))->toContain('subscription has expired');
})->repeat(100);

// Feature: hierarchical-user-management, Property 9: Subscription status affects access
// Validates: Requirements 3.4
test('admin with expired subscription cannot update existing properties', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create admin with expired subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);
    
    // Create expired subscription
    Subscription::factory()->expired()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::EXPIRED->value,
        'expires_at' => now()->subDays(fake()->numberBetween(1, 30)),
    ]);
    
    // Create a property for the admin
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => 'apartment',
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Act as admin with expired subscription
    $this->actingAs($admin);
    
    // Property: Admin with expired subscription should NOT be able to update properties
    $response = $this->put(route('manager.properties.update', $property), [
        'address' => fake()->address(),
        'type' => 'house',
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Should be redirected (302) with error message, not successful (200)
    expect($response->status())->toBe(302)
        ->and($response->headers->get('Location'))->toContain('dashboard');
    
    // Verify error message is in session
    expect(session('error'))->toContain('subscription has expired');
})->repeat(100);

// Feature: hierarchical-user-management, Property 9: Subscription status affects access
// Validates: Requirements 3.4
test('admin with expired subscription cannot delete properties', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create admin with expired subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);
    
    // Create expired subscription
    Subscription::factory()->expired()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::EXPIRED->value,
        'expires_at' => now()->subDays(fake()->numberBetween(1, 30)),
    ]);
    
    // Create a property for the admin
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Act as admin with expired subscription
    $this->actingAs($admin);
    
    // Property: Admin with expired subscription should NOT be able to delete properties
    $response = $this->delete(route('manager.properties.destroy', $property));
    
    // Should be redirected (302) with error message, not successful (200)
    expect($response->status())->toBe(302)
        ->and($response->headers->get('Location'))->toContain('dashboard');
    
    // Verify error message is in session
    expect(session('error'))->toContain('subscription has expired');
    
    // Verify property still exists
    $propertyExists = Property::withoutGlobalScopes()->find($property->id);
    expect($propertyExists)->not->toBeNull();
})->repeat(100);

// Feature: hierarchical-user-management, Property 9: Subscription status affects access
// Validates: Requirements 3.4
test('admin with active subscription can perform both read and write operations', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create admin with active subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);
    
    // Create active subscription
    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->addDays(fake()->numberBetween(1, 365)),
        'max_properties' => 100,
        'max_tenants' => 500,
    ]);
    
    // Act as admin with active subscription
    $this->actingAs($admin);
    
    // Property: Admin with active subscription should be able to perform read operations
    $response = $this->get(route('manager.properties.index'));
    expect($response->status())->toBe(200);
    
    // Property: Admin with active subscription should be able to perform write operations
    $response = $this->post(route('manager.properties.store'), [
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Should succeed (200, 201, or redirect to success page)
    // Note: May redirect to show page after creation, which is still success
    expect($response->status())->toBeIn([200, 201, 302]);
    
    // If redirected, should not be to dashboard with error
    if ($response->status() === 302) {
        expect($response->headers->get('Location'))->not->toContain('dashboard');
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 9: Subscription status affects access
// Validates: Requirements 3.4
test('admin with suspended subscription cannot perform write operations', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create admin with suspended subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);
    
    // Create suspended subscription
    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::SUSPENDED->value,
        'expires_at' => now()->addDays(fake()->numberBetween(1, 30)),
    ]);
    
    // Act as admin with suspended subscription
    $this->actingAs($admin);
    
    // Property: Admin with suspended subscription should NOT be able to create properties
    $response = $this->post(route('manager.properties.store'), [
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Should be redirected (302) with error message, not successful (200 or 201)
    expect($response->status())->toBe(302)
        ->and($response->headers->get('Location'))->toContain('dashboard');
    
    // Verify error message is in session
    expect(session('error'))->toContain('suspended');
})->repeat(100);

// Feature: hierarchical-user-management, Property 9: Subscription status affects access
// Validates: Requirements 3.4
test('admin with no subscription cannot perform write operations', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create admin without subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);
    
    // No subscription created
    
    // Act as admin without subscription
    $this->actingAs($admin);
    
    // Property: Admin without subscription should NOT be able to create properties
    $response = $this->post(route('manager.properties.store'), [
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Should be redirected (302) with error message, not successful (200 or 201)
    expect($response->status())->toBe(302)
        ->and($response->headers->get('Location'))->toContain('dashboard');
    
    // Verify error message is in session
    expect(session('error'))->toContain('No active subscription');
})->repeat(100);
