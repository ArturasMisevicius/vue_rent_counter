<?php

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use App\Services\AccountManagementService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Feature: hierarchical-user-management, Property 5: Tenant inherits admin tenant_id
// Validates: Requirements 5.2
test('tenant account inherits admin tenant_id when created', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create a random number of admin accounts (2-5)
    $adminCount = fake()->numberBetween(2, 5);
    
    for ($adminIndex = 0; $adminIndex < $adminCount; $adminIndex++) {
        // Create an admin account
        $adminData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'organization_name' => fake()->company(),
            'plan_type' => fake()->randomElement(['basic', 'professional', 'enterprise']),
            'expires_at' => now()->addDays(fake()->numberBetween(30, 365))->toDateString(),
        ];
        
        $admin = $accountService->createAdminAccount($adminData, $superadmin);
        
        // Verify admin has a tenant_id
        expect($admin->tenant_id)->not->toBeNull();
        
        // Create a building and property for this admin
        $building = Building::factory()->create([
            'tenant_id' => $admin->tenant_id,
        ]);
        
        $property = Property::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'building_id' => $building->id,
        ]);
        
        // Create a random number of tenant accounts for this admin (1-4)
        $tenantCount = fake()->numberBetween(1, 4);
        
        for ($tenantIndex = 0; $tenantIndex < $tenantCount; $tenantIndex++) {
            $tenantData = [
                'email' => fake()->unique()->safeEmail(),
                'password' => 'password123',
                'name' => fake()->name(),
                'property_id' => $property->id,
            ];
            
            $tenant = $accountService->createTenantAccount($tenantData, $admin);
            
            // Property: For any tenant account created by an admin, 
            // the tenant's tenant_id should equal the admin's tenant_id
            expect($tenant->tenant_id)->toBe($admin->tenant_id)
                ->and($tenant->tenant_id)->not->toBeNull()
                ->and($tenant->parent_user_id)->toBe($admin->id);
        }
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 5: Tenant inherits admin tenant_id
// Validates: Requirements 5.2
test('tenant tenant_id inheritance is persisted in database', function () {
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
        'plan_type' => fake()->randomElement(['basic', 'professional', 'enterprise']),
        'expires_at' => now()->addDays(fake()->numberBetween(30, 365))->toDateString(),
    ];
    
    $admin = $accountService->createAdminAccount($adminData, $superadmin);
    $adminTenantId = $admin->tenant_id;
    
    // Create a building and property
    $building = Building::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);
    
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building->id,
    ]);
    
    // Create multiple tenant accounts
    $tenantCount = fake()->numberBetween(2, 6);
    $tenantIds = [];
    
    for ($i = 0; $i < $tenantCount; $i++) {
        $tenantData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'property_id' => $property->id,
        ];
        
        $tenant = $accountService->createTenantAccount($tenantData, $admin);
        $tenantIds[] = $tenant->id;
    }
    
    // Property: Reload tenants from database and verify tenant_id inheritance is persisted
    $reloadedTenants = User::whereIn('id', $tenantIds)->get();
    
    foreach ($reloadedTenants as $tenant) {
        expect($tenant->tenant_id)->toBe($adminTenantId)
            ->and($tenant->parent_user_id)->toBe($admin->id);
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 5: Tenant inherits admin tenant_id
// Validates: Requirements 5.2
test('multiple admins create tenants with correct tenant_id inheritance', function () {
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
    $adminTenantMap = [];
    
    for ($i = 0; $i < $adminCount; $i++) {
        // Create an admin account
        $adminData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'organization_name' => fake()->company(),
            'plan_type' => fake()->randomElement(['basic', 'professional', 'enterprise']),
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
        
        // Create tenants for this admin
        $tenantCount = fake()->numberBetween(1, 3);
        $tenantIds = [];
        
        for ($j = 0; $j < $tenantCount; $j++) {
            $tenantData = [
                'email' => fake()->unique()->safeEmail(),
                'password' => 'password123',
                'name' => fake()->name(),
                'property_id' => $property->id,
            ];
            
            $tenant = $accountService->createTenantAccount($tenantData, $admin);
            $tenantIds[] = $tenant->id;
        }
        
        $adminTenantMap[$admin->id] = [
            'tenant_id' => $admin->tenant_id,
            'tenant_ids' => $tenantIds,
        ];
    }
    
    // Property: Verify each tenant has the correct tenant_id matching their admin
    foreach ($adminTenantMap as $adminId => $data) {
        $tenants = User::whereIn('id', $data['tenant_ids'])->get();
        
        foreach ($tenants as $tenant) {
            expect($tenant->tenant_id)->toBe($data['tenant_id'])
                ->and($tenant->parent_user_id)->toBe($adminId);
        }
    }
    
    // Property: Verify tenants from different admins have different tenant_ids
    $allAdminTenantIds = array_column($adminTenantMap, 'tenant_id');
    $uniqueTenantIds = array_unique($allAdminTenantIds);
    expect(count($uniqueTenantIds))->toBe(count($allAdminTenantIds));
})->repeat(100);

// Feature: hierarchical-user-management, Property 5: Tenant inherits admin tenant_id
// Validates: Requirements 5.2
test('tenant cannot be created with different tenant_id than admin', function () {
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
        'plan_type' => 'basic',
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin1 = $accountService->createAdminAccount($admin1Data, $superadmin);
    
    $admin2Data = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'organization_name' => fake()->company(),
        'plan_type' => 'basic',
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
    
    // Property: Admin1 should not be able to create a tenant for admin2's property
    // This should throw an InvalidPropertyAssignmentException
    $tenantData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $property2->id,
    ];
    
    expect(fn() => $accountService->createTenantAccount($tenantData, $admin1))
        ->toThrow(\App\Exceptions\InvalidPropertyAssignmentException::class);
})->repeat(100);

// Feature: hierarchical-user-management, Property 5: Tenant inherits admin tenant_id
// Validates: Requirements 5.2
test('tenant tenant_id matches property tenant_id after creation', function () {
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
        'plan_type' => fake()->randomElement(['basic', 'professional', 'enterprise']),
        'expires_at' => now()->addDays(fake()->numberBetween(30, 365))->toDateString(),
    ];
    
    $admin = $accountService->createAdminAccount($adminData, $superadmin);
    
    // Create multiple properties for this admin
    $propertyCount = fake()->numberBetween(2, 5);
    
    for ($i = 0; $i < $propertyCount; $i++) {
        $building = Building::factory()->create([
            'tenant_id' => $admin->tenant_id,
        ]);
        
        $property = Property::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'building_id' => $building->id,
        ]);
        
        // Create a tenant for this property
        $tenantData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'property_id' => $property->id,
        ];
        
        $tenant = $accountService->createTenantAccount($tenantData, $admin);
        
        // Property: Tenant's tenant_id should match both admin's and property's tenant_id
        expect($tenant->tenant_id)->toBe($admin->tenant_id)
            ->and($tenant->tenant_id)->toBe($property->tenant_id)
            ->and($tenant->property_id)->toBe($property->id);
    }
})->repeat(100);
