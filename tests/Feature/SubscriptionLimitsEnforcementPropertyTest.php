<?php

use App\Enums\UserRole;
use App\Enums\SubscriptionStatus;
use App\Exceptions\SubscriptionLimitExceededException;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AccountManagementService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Feature: hierarchical-user-management, Property 17: Subscription limits enforcement
// Validates: Requirements 2.5
test('admin cannot create property beyond subscription max_properties limit', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create admin with active subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);
    
    // Set a random but small property limit
    $maxProperties = fake()->numberBetween(1, 5);
    
    // Create active subscription with specific property limit
    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->addDays(fake()->numberBetween(30, 365)),
        'max_properties' => $maxProperties,
        'max_tenants' => 100,
    ]);
    
    // Create properties up to the limit
    for ($i = 0; $i < $maxProperties; $i++) {
        Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'address' => fake()->address(),
            'type' => fake()->randomElement(['apartment', 'house']),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
        ]);
    }
    
    // Verify we're at the limit
    $currentPropertyCount = $admin->properties()->count();
    expect($currentPropertyCount)->toBe($maxProperties);
    
    // Property: Attempting to create another property should fail
    $subscriptionService = app(SubscriptionService::class);
    
    expect(fn() => $subscriptionService->enforceSubscriptionLimits($admin, 'property'))
        ->toThrow(SubscriptionLimitExceededException::class);
})->repeat(100);

// Feature: hierarchical-user-management, Property 17: Subscription limits enforcement
// Validates: Requirements 2.5
test('admin cannot create tenant beyond subscription max_tenants limit', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create admin with active subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);
    
    // Set a random but small tenant limit
    $maxTenants = fake()->numberBetween(1, 5);
    
    // Create active subscription with specific tenant limit
    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->addDays(fake()->numberBetween(30, 365)),
        'max_properties' => 100,
        'max_tenants' => $maxTenants,
    ]);
    
    // Create a property for tenant assignment
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Create tenants up to the limit
    for ($i = 0; $i < $maxTenants; $i++) {
        User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $tenantId,
            'parent_user_id' => $admin->id,
            'property_id' => $property->id,
            'is_active' => true,
        ]);
    }
    
    // Verify we're at the limit
    $currentTenantCount = $admin->childUsers()->where('role', 'tenant')->count();
    expect($currentTenantCount)->toBe($maxTenants);
    
    // Property: Attempting to create another tenant should fail
    $subscriptionService = app(SubscriptionService::class);
    
    expect(fn() => $subscriptionService->enforceSubscriptionLimits($admin, 'tenant'))
        ->toThrow(SubscriptionLimitExceededException::class);
})->repeat(100);

// Feature: hierarchical-user-management, Property 17: Subscription limits enforcement
// Validates: Requirements 2.5
test('admin can create property when under subscription limit', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create admin with active subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);
    
    // Set a property limit
    $maxProperties = fake()->numberBetween(5, 10);
    
    // Create active subscription
    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->addDays(fake()->numberBetween(30, 365)),
        'max_properties' => $maxProperties,
        'max_tenants' => 100,
    ]);
    
    // Create properties below the limit
    $propertiesToCreate = fake()->numberBetween(1, $maxProperties - 1);
    for ($i = 0; $i < $propertiesToCreate; $i++) {
        Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'address' => fake()->address(),
            'type' => fake()->randomElement(['apartment', 'house']),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
        ]);
    }
    
    // Verify we're under the limit
    $currentPropertyCount = $admin->properties()->count();
    expect($currentPropertyCount)->toBeLessThan($maxProperties);
    
    // Property: Attempting to create another property should succeed
    $subscriptionService = app(SubscriptionService::class);
    
    // Should not throw exception
    $subscriptionService->enforceSubscriptionLimits($admin, 'property');
    
    expect(true)->toBeTrue(); // If no exception, test passes
})->repeat(100);

// Feature: hierarchical-user-management, Property 17: Subscription limits enforcement
// Validates: Requirements 2.5
test('admin can create tenant when under subscription limit', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create admin with active subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);
    
    // Set a tenant limit
    $maxTenants = fake()->numberBetween(5, 10);
    
    // Create active subscription
    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->addDays(fake()->numberBetween(30, 365)),
        'max_properties' => 100,
        'max_tenants' => $maxTenants,
    ]);
    
    // Create a property for tenant assignment
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Create tenants below the limit
    $tenantsToCreate = fake()->numberBetween(1, $maxTenants - 1);
    for ($i = 0; $i < $tenantsToCreate; $i++) {
        User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $tenantId,
            'parent_user_id' => $admin->id,
            'property_id' => $property->id,
            'is_active' => true,
        ]);
    }
    
    // Verify we're under the limit
    $currentTenantCount = $admin->childUsers()->where('role', 'tenant')->count();
    expect($currentTenantCount)->toBeLessThan($maxTenants);
    
    // Property: Attempting to create another tenant should succeed
    $subscriptionService = app(SubscriptionService::class);
    
    // Should not throw exception
    $subscriptionService->enforceSubscriptionLimits($admin, 'tenant');
    
    expect(true)->toBeTrue(); // If no exception, test passes
})->repeat(100);

// Feature: hierarchical-user-management, Property 17: Subscription limits enforcement
// Validates: Requirements 2.5
test('AccountManagementService enforces property limit when creating tenant', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create admin with active subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);
    
    // Set a small tenant limit
    $maxTenants = fake()->numberBetween(1, 3);
    
    // Create active subscription with specific tenant limit
    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->addDays(fake()->numberBetween(30, 365)),
        'max_properties' => 100,
        'max_tenants' => $maxTenants,
    ]);
    
    // Create a property for tenant assignment
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Create tenants up to the limit
    for ($i = 0; $i < $maxTenants; $i++) {
        User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $tenantId,
            'parent_user_id' => $admin->id,
            'property_id' => $property->id,
            'is_active' => true,
        ]);
    }
    
    // Property: AccountManagementService should throw exception when creating tenant beyond limit
    $accountService = app(AccountManagementService::class);
    
    expect(fn() => $accountService->createTenantAccount([
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $property->id,
    ], $admin))->toThrow(SubscriptionLimitExceededException::class);
})->repeat(100);

// Feature: hierarchical-user-management, Property 17: Subscription limits enforcement
// Validates: Requirements 2.5
test('subscription limit check correctly counts existing resources', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create admin with active subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);
    
    // Set random limits
    $maxProperties = fake()->numberBetween(5, 10);
    $maxTenants = fake()->numberBetween(5, 10);
    
    // Create active subscription
    $subscription = Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->addDays(fake()->numberBetween(30, 365)),
        'max_properties' => $maxProperties,
        'max_tenants' => $maxTenants,
    ]);
    
    // Create random number of properties (less than limit)
    $propertyCount = fake()->numberBetween(1, $maxProperties - 1);
    for ($i = 0; $i < $propertyCount; $i++) {
        Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'address' => fake()->address(),
            'type' => fake()->randomElement(['apartment', 'house']),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
        ]);
    }
    
    // Create random number of tenants (less than limit)
    $tenantCount = fake()->numberBetween(1, $maxTenants - 1);
    $property = Property::withoutGlobalScopes()->first();
    for ($i = 0; $i < $tenantCount; $i++) {
        User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $tenantId,
            'parent_user_id' => $admin->id,
            'property_id' => $property->id,
            'is_active' => true,
        ]);
    }
    
    // Property: canAddProperty should return true when under limit
    expect($subscription->canAddProperty())->toBeTrue();
    
    // Property: canAddTenant should return true when under limit
    expect($subscription->canAddTenant())->toBeTrue();
    
    // Verify actual counts match expected
    $actualPropertyCount = $admin->properties()->count();
    $actualTenantCount = $admin->childUsers()->where('role', 'tenant')->count();
    
    expect($actualPropertyCount)->toBe($propertyCount)
        ->and($actualTenantCount)->toBe($tenantCount);
})->repeat(100);

// Feature: hierarchical-user-management, Property 17: Subscription limits enforcement
// Validates: Requirements 2.5
test('subscription limit check returns false when at limit', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create admin with active subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);
    
    // Set random limits
    $maxProperties = fake()->numberBetween(2, 5);
    $maxTenants = fake()->numberBetween(2, 5);
    
    // Create active subscription
    $subscription = Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->addDays(fake()->numberBetween(30, 365)),
        'max_properties' => $maxProperties,
        'max_tenants' => $maxTenants,
    ]);
    
    // Create properties up to the limit
    for ($i = 0; $i < $maxProperties; $i++) {
        Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'address' => fake()->address(),
            'type' => fake()->randomElement(['apartment', 'house']),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
        ]);
    }
    
    // Create tenants up to the limit
    $property = Property::withoutGlobalScopes()->first();
    for ($i = 0; $i < $maxTenants; $i++) {
        User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $tenantId,
            'parent_user_id' => $admin->id,
            'property_id' => $property->id,
            'is_active' => true,
        ]);
    }
    
    // Property: canAddProperty should return false when at limit
    expect($subscription->canAddProperty())->toBeFalse();
    
    // Property: canAddTenant should return false when at limit
    expect($subscription->canAddTenant())->toBeFalse();
})->repeat(100);

// Feature: hierarchical-user-management, Property 17: Subscription limits enforcement
// Validates: Requirements 2.5
test('expired subscription prevents resource creation even when under limits', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create admin with expired subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);
    
    // Create expired subscription with high limits
    $subscription = Subscription::factory()->expired()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::EXPIRED->value,
        'expires_at' => now()->subDays(fake()->numberBetween(1, 30)),
        'max_properties' => 100,
        'max_tenants' => 100,
    ]);
    
    // Don't create any properties or tenants (well under limit)
    
    // Property: canAddProperty should return false for expired subscription
    expect($subscription->canAddProperty())->toBeFalse();
    
    // Property: canAddTenant should return false for expired subscription
    expect($subscription->canAddTenant())->toBeFalse();
})->repeat(100);
