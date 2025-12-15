<?php

use App\Enums\UserRole;
use App\Enums\SubscriptionStatus;
use App\Models\Building;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Feature: hierarchical-user-management, Property 10: Subscription renewal restores access
// Validates: Requirements 3.5
test('subscription renewal restores write access for expired subscriptions', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create admin with expired subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);
    
    // Create expired subscription
    $subscription = Subscription::factory()->expired()->create([
        'user_id' => $admin->id,
        'status' => 'expired',
        'expires_at' => now()->subDays(fake()->numberBetween(1, 30)),
        'max_properties' => 100,
        'max_tenants' => 500,
    ]);
    
    // Act as admin with expired subscription
    $this->actingAs($admin);
    
    // Verify write operations fail with expired subscription
    $responseBeforeRenewal = $this->post(route('manager.properties.store'), [
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Should fail (not 200 or 201)
    expect($responseBeforeRenewal->status())->not->toBe(200)
        ->and($responseBeforeRenewal->status())->not->toBe(201);
    
    // Renew the subscription
    $subscriptionService = app(SubscriptionService::class);
    $newExpiryDate = now()->addDays(fake()->numberBetween(30, 365));
    $subscriptionService->renewSubscription($subscription, $newExpiryDate);
    
    // Refresh the admin to get updated subscription
    $admin->refresh();
    
    // Property: After renewal, write operations should succeed
    $responseAfterRenewal = $this->post(route('manager.properties.store'), [
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Should succeed (200, 201, or redirect to success page)
    expect($responseAfterRenewal->status())->toBeIn([200, 201, 302]);
    
    // Verify subscription is now active
    $subscription->refresh();
    expect($subscription->status)->toBe(SubscriptionStatus::ACTIVE)
        ->and($subscription->isActive())->toBeTrue()
        ->and($subscription->expires_at->isFuture())->toBeTrue();
})->repeat(100);

// Feature: hierarchical-user-management, Property 10: Subscription renewal restores access
// Validates: Requirements 3.5
test('subscription renewal restores write access for suspended subscriptions', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create admin with suspended subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);
    
    // Create suspended subscription
    $subscription = Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => 'suspended',
        'expires_at' => now()->addDays(fake()->numberBetween(1, 30)),
        'max_properties' => 100,
        'max_tenants' => 500,
    ]);
    
    // Act as admin with suspended subscription
    $this->actingAs($admin);
    
    // Verify write operations fail with suspended subscription
    $responseBeforeRenewal = $this->post(route('manager.buildings.store'), [
        'name' => fake()->company(),
        'address' => fake()->address(),
    ]);
    
    // Should fail (not 200 or 201)
    expect($responseBeforeRenewal->status())->not->toBe(200)
        ->and($responseBeforeRenewal->status())->not->toBe(201);
    
    // Renew the subscription (which also reactivates it)
    $subscriptionService = app(SubscriptionService::class);
    $newExpiryDate = now()->addDays(fake()->numberBetween(30, 365));
    $subscriptionService->renewSubscription($subscription, $newExpiryDate);
    
    // Refresh the admin to get updated subscription
    $admin->refresh();
    
    // Property: After renewal, write operations should succeed
    $responseAfterRenewal = $this->post(route('manager.buildings.store'), [
        'name' => fake()->company(),
        'address' => fake()->address(),
    ]);
    
    // Should succeed (200, 201, or redirect to success page)
    expect($responseAfterRenewal->status())->toBeIn([200, 201, 302]);
    
    // Verify subscription is now active
    $subscription->refresh();
    expect($subscription->status)->toBe(SubscriptionStatus::ACTIVE)
        ->and($subscription->isActive())->toBeTrue();
})->repeat(100);

// Feature: hierarchical-user-management, Property 10: Subscription renewal restores access
// Validates: Requirements 3.5
test('subscription renewal allows property updates that were previously blocked', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create admin with expired subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);
    
    // Create a property for the admin
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => 'apartment',
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Create expired subscription
    $subscription = Subscription::factory()->expired()->create([
        'user_id' => $admin->id,
        'status' => 'expired',
        'expires_at' => now()->subDays(fake()->numberBetween(1, 30)),
        'max_properties' => 100,
        'max_tenants' => 500,
    ]);
    
    // Act as admin with expired subscription
    $this->actingAs($admin);
    
    // Verify update operations fail with expired subscription
    $responseBeforeRenewal = $this->put(route('manager.properties.update', $property), [
        'address' => fake()->address(),
        'type' => 'house',
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Should fail (not 200)
    expect($responseBeforeRenewal->status())->not->toBe(200);
    
    // Renew the subscription
    $subscriptionService = app(SubscriptionService::class);
    $newExpiryDate = now()->addDays(fake()->numberBetween(30, 365));
    $subscriptionService->renewSubscription($subscription, $newExpiryDate);
    
    // Refresh the admin to get updated subscription
    $admin->refresh();
    
    // Property: After renewal, update operations should succeed
    $responseAfterRenewal = $this->put(route('manager.properties.update', $property), [
        'address' => fake()->address(),
        'type' => 'house',
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Should succeed (200 or redirect to success page)
    expect($responseAfterRenewal->status())->toBeIn([200, 302]);
})->repeat(100);

// Feature: hierarchical-user-management, Property 10: Subscription renewal restores access
// Validates: Requirements 3.5
test('subscription renewal allows property deletions that were previously blocked', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create admin with expired subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);
    
    // Create properties for the admin
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Create expired subscription
    $subscription = Subscription::factory()->expired()->create([
        'user_id' => $admin->id,
        'status' => 'expired',
        'expires_at' => now()->subDays(fake()->numberBetween(1, 30)),
        'max_properties' => 100,
        'max_tenants' => 500,
    ]);
    
    // Act as admin with expired subscription
    $this->actingAs($admin);
    
    // Verify delete operations fail with expired subscription
    $responseBeforeRenewal = $this->delete(route('manager.properties.destroy', $property1));
    
    // Should fail (not 200)
    expect($responseBeforeRenewal->status())->not->toBe(200);
    
    // Verify property still exists
    $propertyExists = Property::withoutGlobalScopes()->find($property1->id);
    expect($propertyExists)->not->toBeNull();
    
    // Renew the subscription
    $subscriptionService = app(SubscriptionService::class);
    $newExpiryDate = now()->addDays(fake()->numberBetween(30, 365));
    $subscriptionService->renewSubscription($subscription, $newExpiryDate);
    
    // Refresh the admin to get updated subscription
    $admin->refresh();
    
    // Property: After renewal, delete operations should succeed
    $responseAfterRenewal = $this->delete(route('manager.properties.destroy', $property2));
    
    // Should succeed (200 or redirect to success page)
    expect($responseAfterRenewal->status())->toBeIn([200, 302]);
})->repeat(100);

// Feature: hierarchical-user-management, Property 10: Subscription renewal restores access
// Validates: Requirements 3.5
test('subscription renewal restores all write operations across different resource types', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create admin with expired subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);
    
    // Create expired subscription
    $subscription = Subscription::factory()->expired()->create([
        'user_id' => $admin->id,
        'status' => 'expired',
        'expires_at' => now()->subDays(fake()->numberBetween(1, 30)),
        'max_properties' => 100,
        'max_tenants' => 500,
    ]);
    
    // Act as admin with expired subscription
    $this->actingAs($admin);
    
    // Verify multiple write operations fail with expired subscription
    $propertyResponse = $this->post(route('manager.properties.store'), [
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $buildingResponse = $this->post(route('manager.buildings.store'), [
        'name' => fake()->company(),
        'address' => fake()->address(),
    ]);
    
    // Both should fail
    expect($propertyResponse->status())->not->toBe(200)
        ->and($propertyResponse->status())->not->toBe(201)
        ->and($buildingResponse->status())->not->toBe(200)
        ->and($buildingResponse->status())->not->toBe(201);
    
    // Renew the subscription
    $subscriptionService = app(SubscriptionService::class);
    $newExpiryDate = now()->addDays(fake()->numberBetween(30, 365));
    $subscriptionService->renewSubscription($subscription, $newExpiryDate);
    
    // Refresh the admin to get updated subscription
    $admin->refresh();
    
    // Property: After renewal, all write operations should succeed
    $propertyResponseAfter = $this->post(route('manager.properties.store'), [
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $buildingResponseAfter = $this->post(route('manager.buildings.store'), [
        'name' => fake()->company(),
        'address' => fake()->address(),
    ]);
    
    // Both should succeed
    expect($propertyResponseAfter->status())->toBeIn([200, 201, 302])
        ->and($buildingResponseAfter->status())->toBeIn([200, 201, 302]);
})->repeat(100);

// Feature: hierarchical-user-management, Property 10: Subscription renewal restores access
// Validates: Requirements 3.5
test('subscription renewal maintains read access throughout the process', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create admin with expired subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);
    
    // Create some properties for the admin to read
    $propertyCount = fake()->numberBetween(2, 5);
    for ($i = 0; $i < $propertyCount; $i++) {
        Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'address' => fake()->address(),
            'type' => fake()->randomElement(['apartment', 'house']),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
        ]);
    }
    
    // Create expired subscription
    $subscription = Subscription::factory()->expired()->create([
        'user_id' => $admin->id,
        'status' => 'expired',
        'expires_at' => now()->subDays(fake()->numberBetween(1, 30)),
        'max_properties' => 100,
        'max_tenants' => 500,
    ]);
    
    // Act as admin with expired subscription
    $this->actingAs($admin);
    
    // Verify read operations work before renewal
    $responseBeforeRenewal = $this->get(route('manager.properties.index'));
    expect($responseBeforeRenewal->status())->toBe(200);
    
    $propertiesBeforeRenewal = Property::all();
    expect($propertiesBeforeRenewal)->toHaveCount($propertyCount);
    
    // Renew the subscription
    $subscriptionService = app(SubscriptionService::class);
    $newExpiryDate = now()->addDays(fake()->numberBetween(30, 365));
    $subscriptionService->renewSubscription($subscription, $newExpiryDate);
    
    // Refresh the admin to get updated subscription
    $admin->refresh();
    
    // Property: Read operations should still work after renewal
    $responseAfterRenewal = $this->get(route('manager.properties.index'));
    expect($responseAfterRenewal->status())->toBe(200);
    
    $propertiesAfterRenewal = Property::all();
    expect($propertiesAfterRenewal)->toHaveCount($propertyCount);
})->repeat(100);
