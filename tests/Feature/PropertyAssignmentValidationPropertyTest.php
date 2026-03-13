<?php

use App\Enums\UserRole;
use App\Enums\SubscriptionPlanType;
use App\Exceptions\InvalidPropertyAssignmentException;
use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use App\Services\AccountManagementService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Feature: hierarchical-user-management, Property 8: Property assignment validation
// Validates: Requirements 5.3, 6.1
test('tenant assignment to property succeeds when property tenant_id matches admin tenant_id', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create an admin account
    $adminData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'organization_name' => fake()->company(),
        'plan_type' => fake()->randomElement(SubscriptionPlanType::values()),
        'expires_at' => now()->addDays(fake()->numberBetween(30, 365))->toDateString(),
    ];
    
    $admin = $accountService->createAdminAccount($adminData, $superadmin);
    
    // Create a building and property for this admin
    $building = Building::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);
    
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building->id,
    ]);
    
    // Property: For any tenant assignment to a property, 
    // if the property's tenant_id matches the admin's tenant_id, the assignment should succeed
    $tenantData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $property->id,
    ];
    
    $tenant = $accountService->createTenantAccount($tenantData, $admin);
    
    // Verify the tenant was created successfully
    expect($tenant)->not->toBeNull()
        ->and($tenant->tenant_id)->toBe($admin->tenant_id)
        ->and($tenant->property_id)->toBe($property->id)
        ->and($tenant->parent_user_id)->toBe($admin->id);
})->repeat(100);

// Feature: hierarchical-user-management, Property 8: Property assignment validation
// Validates: Requirements 5.3, 6.1
test('tenant assignment to property fails when property tenant_id does not match admin tenant_id', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create two admin accounts with different tenant_ids
    $admin1Data = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'organization_name' => fake()->company(),
        'plan_type' => SubscriptionPlanType::BASIC->value,
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin1 = $accountService->createAdminAccount($admin1Data, $superadmin);
    
    $admin2Data = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'organization_name' => fake()->company(),
        'plan_type' => SubscriptionPlanType::BASIC->value,
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin2 = $accountService->createAdminAccount($admin2Data, $superadmin);
    
    // Verify admins have different tenant_ids
    expect($admin1->tenant_id)->not->toBe($admin2->tenant_id);
    
    // Create a property for admin2
    $building2 = Building::factory()->create([
        'tenant_id' => $admin2->tenant_id,
    ]);
    
    $property2 = Property::factory()->create([
        'tenant_id' => $admin2->tenant_id,
        'building_id' => $building2->id,
    ]);
    
    // Property: For any tenant assignment to a property, 
    // if the property's tenant_id does not match the admin's tenant_id, the assignment should fail
    $tenantData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $property2->id,
    ];
    
    expect(fn() => $accountService->createTenantAccount($tenantData, $admin1))
        ->toThrow(InvalidPropertyAssignmentException::class);
})->repeat(100);

// Feature: hierarchical-user-management, Property 8: Property assignment validation
// Validates: Requirements 5.3, 6.1
test('tenant reassignment to property succeeds when property tenant_id matches admin tenant_id', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create an admin account
    $adminData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'organization_name' => fake()->company(),
        'plan_type' => fake()->randomElement(SubscriptionPlanType::values()),
        'expires_at' => now()->addDays(fake()->numberBetween(30, 365))->toDateString(),
    ];
    
    $admin = $accountService->createAdminAccount($adminData, $superadmin);
    
    // Create a building and two properties for this admin
    $building = Building::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);
    
    $property1 = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building->id,
    ]);
    
    $property2 = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building->id,
    ]);
    
    // Create a tenant assigned to property1
    $tenantData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $property1->id,
    ];
    
    $tenant = $accountService->createTenantAccount($tenantData, $admin);
    
    // Verify initial assignment
    expect($tenant->property_id)->toBe($property1->id);
    
    // Property: Reassigning tenant to property2 (same tenant_id) should succeed
    $accountService->reassignTenant($tenant, $property2, $admin);
    
    // Verify reassignment succeeded
    $tenant->refresh();
    expect($tenant->property_id)->toBe($property2->id)
        ->and($tenant->tenant_id)->toBe($admin->tenant_id);
})->repeat(100);

// Feature: hierarchical-user-management, Property 8: Property assignment validation
// Validates: Requirements 5.3, 6.1
test('tenant reassignment to property fails when property tenant_id does not match admin tenant_id', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create two admin accounts with different tenant_ids
    $admin1Data = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'organization_name' => fake()->company(),
        'plan_type' => SubscriptionPlanType::BASIC->value,
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin1 = $accountService->createAdminAccount($admin1Data, $superadmin);
    
    $admin2Data = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'organization_name' => fake()->company(),
        'plan_type' => SubscriptionPlanType::BASIC->value,
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin2 = $accountService->createAdminAccount($admin2Data, $superadmin);
    
    // Verify admins have different tenant_ids
    expect($admin1->tenant_id)->not->toBe($admin2->tenant_id);
    
    // Create a property for admin1
    $building1 = Building::factory()->create([
        'tenant_id' => $admin1->tenant_id,
    ]);
    
    $property1 = Property::factory()->create([
        'tenant_id' => $admin1->tenant_id,
        'building_id' => $building1->id,
    ]);
    
    // Create a tenant for admin1
    $tenantData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $property1->id,
    ];
    
    $tenant = $accountService->createTenantAccount($tenantData, $admin1);
    
    // Create a property for admin2
    $building2 = Building::factory()->create([
        'tenant_id' => $admin2->tenant_id,
    ]);
    
    $property2 = Property::factory()->create([
        'tenant_id' => $admin2->tenant_id,
        'building_id' => $building2->id,
    ]);
    
    // Property: Admin1 attempting to reassign their tenant to admin2's property should fail
    expect(fn() => $accountService->reassignTenant($tenant, $property2, $admin1))
        ->toThrow(InvalidPropertyAssignmentException::class);
})->repeat(100);

// Feature: hierarchical-user-management, Property 8: Property assignment validation
// Validates: Requirements 5.3, 6.1
test('assignTenantToProperty validates property tenant_id matches admin tenant_id', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create two admin accounts
    $admin1Data = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'organization_name' => fake()->company(),
        'plan_type' => SubscriptionPlanType::BASIC->value,
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin1 = $accountService->createAdminAccount($admin1Data, $superadmin);
    
    $admin2Data = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'organization_name' => fake()->company(),
        'plan_type' => SubscriptionPlanType::BASIC->value,
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin2 = $accountService->createAdminAccount($admin2Data, $superadmin);
    
    // Create a property for admin1
    $building1 = Building::factory()->create([
        'tenant_id' => $admin1->tenant_id,
    ]);
    
    $property1 = Property::factory()->create([
        'tenant_id' => $admin1->tenant_id,
        'building_id' => $building1->id,
    ]);
    
    // Create a tenant for admin1 (initially without property assignment)
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $admin1->tenant_id,
        'parent_user_id' => $admin1->id,
        'property_id' => null,
        'is_active' => true,
    ]);
    
    // Property: Assigning tenant to property with matching tenant_id should succeed
    $accountService->assignTenantToProperty($tenant, $property1, $admin1);
    
    $tenant->refresh();
    expect($tenant->property_id)->toBe($property1->id);
    
    // Create a property for admin2
    $building2 = Building::factory()->create([
        'tenant_id' => $admin2->tenant_id,
    ]);
    
    $property2 = Property::factory()->create([
        'tenant_id' => $admin2->tenant_id,
        'building_id' => $building2->id,
    ]);
    
    // Property: Admin1 attempting to assign their tenant to admin2's property should fail
    expect(fn() => $accountService->assignTenantToProperty($tenant, $property2, $admin1))
        ->toThrow(InvalidPropertyAssignmentException::class);
})->repeat(100);

// Feature: hierarchical-user-management, Property 8: Property assignment validation
// Validates: Requirements 5.3, 6.1
test('property assignment validation works across multiple admins and properties', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create multiple admin accounts
    $adminCount = fake()->numberBetween(2, 4);
    $admins = [];
    $properties = [];
    
    for ($i = 0; $i < $adminCount; $i++) {
        // Create admin
        $adminData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'organization_name' => fake()->company(),
            'plan_type' => SubscriptionPlanType::BASIC->value,
            'expires_at' => now()->addDays(365)->toDateString(),
        ];
        
        $admin = $accountService->createAdminAccount($adminData, $superadmin);
        $admins[] = $admin;
        
        // Create properties for this admin
        $propertyCount = fake()->numberBetween(1, 3);
        $adminProperties = [];
        
        for ($j = 0; $j < $propertyCount; $j++) {
            $building = Building::factory()->create([
                'tenant_id' => $admin->tenant_id,
            ]);
            
            $property = Property::factory()->create([
                'tenant_id' => $admin->tenant_id,
                'building_id' => $building->id,
            ]);
            
            $adminProperties[] = $property;
        }
        
        $properties[$admin->id] = $adminProperties;
    }
    
    // Property: Each admin should be able to create tenants for their own properties
    foreach ($admins as $admin) {
        $adminProperties = $properties[$admin->id];
        $randomProperty = $adminProperties[array_rand($adminProperties)];
        
        $tenantData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'property_id' => $randomProperty->id,
        ];
        
        $tenant = $accountService->createTenantAccount($tenantData, $admin);
        
        expect($tenant->tenant_id)->toBe($admin->tenant_id)
            ->and($tenant->property_id)->toBe($randomProperty->id);
    }
    
    // Property: Each admin should NOT be able to create tenants for other admins' properties
    if (count($admins) >= 2) {
        $admin1 = $admins[0];
        $admin2 = $admins[1];
        
        $admin2Property = $properties[$admin2->id][0];
        
        $tenantData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'property_id' => $admin2Property->id,
        ];
        
        expect(fn() => $accountService->createTenantAccount($tenantData, $admin1))
            ->toThrow(InvalidPropertyAssignmentException::class);
    }
})->repeat(100);
