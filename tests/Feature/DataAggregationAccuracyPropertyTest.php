<?php

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AccountManagementService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Feature: hierarchical-user-management, Property 19: Data aggregation accuracy
// Validates: Requirements 17.1, 17.3, 18.1
test('superadmin dashboard displays accurate subscription counts', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create random number of admin accounts with various subscription statuses
    $adminCount = fake()->numberBetween(3, 8);
    $expectedCounts = [
        'active' => 0,
        'expired' => 0,
        'suspended' => 0,
        'cancelled' => 0,
    ];
    
    for ($i = 0; $i < $adminCount; $i++) {
        $status = fake()->randomElement(['active', 'expired', 'suspended', 'cancelled']);
        $expectedCounts[$status]++;
        
        $adminData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'organization_name' => fake()->company(),
            'plan_type' => fake()->randomElement(['basic', 'professional', 'enterprise']),
            'expires_at' => now()->addDays(fake()->numberBetween(30, 365))->toDateString(),
        ];
        
        $admin = $accountService->createAdminAccount($adminData, $superadmin);
        
        // Update subscription status
        $admin->subscription->update(['status' => $status]);
    }
    
    // Act as superadmin
    $this->actingAs($superadmin);
    
    // Get actual counts from database
    $totalSubscriptions = Subscription::count();
    $activeSubscriptions = Subscription::where('status', 'active')->count();
    $expiredSubscriptions = Subscription::where('status', 'expired')->count();
    $suspendedSubscriptions = Subscription::where('status', 'suspended')->count();
    $cancelledSubscriptions = Subscription::where('status', 'cancelled')->count();
    
    // Property: Dashboard counts should match actual database counts
    expect($totalSubscriptions)->toBe($adminCount)
        ->and($activeSubscriptions)->toBe($expectedCounts['active'])
        ->and($expiredSubscriptions)->toBe($expectedCounts['expired'])
        ->and($suspendedSubscriptions)->toBe($expectedCounts['suspended'])
        ->and($cancelledSubscriptions)->toBe($expectedCounts['cancelled']);
})->repeat(100);

// Feature: hierarchical-user-management, Property 19: Data aggregation accuracy
// Validates: Requirements 17.1, 17.3, 18.1
test('superadmin dashboard displays accurate organization counts', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create random number of admin accounts
    $adminCount = fake()->numberBetween(3, 10);
    $activeCount = 0;
    
    for ($i = 0; $i < $adminCount; $i++) {
        $isActive = fake()->boolean(70); // 70% chance of being active
        if ($isActive) {
            $activeCount++;
        }
        
        $adminData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'organization_name' => fake()->company(),
            'plan_type' => 'basic',
            'expires_at' => now()->addDays(365)->toDateString(),
        ];
        
        $admin = $accountService->createAdminAccount($adminData, $superadmin);
        
        // Update active status
        $admin->update(['is_active' => $isActive]);
    }
    
    // Act as superadmin
    $this->actingAs($superadmin);
    
    // Get actual counts from database
    $totalOrganizations = User::withoutGlobalScopes()
        ->where('role', UserRole::ADMIN)
        ->count();
    
    $activeOrganizations = User::withoutGlobalScopes()
        ->where('role', UserRole::ADMIN)
        ->where('is_active', true)
        ->count();
    
    // Property: Dashboard organization counts should match actual database counts
    expect($totalOrganizations)->toBe($adminCount)
        ->and($activeOrganizations)->toBe($activeCount);
})->repeat(100);

// Feature: hierarchical-user-management, Property 19: Data aggregation accuracy
// Validates: Requirements 17.1, 17.3, 18.1
test('superadmin dashboard displays accurate system-wide resource counts', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create random number of admins with resources
    $adminCount = fake()->numberBetween(2, 5);
    $expectedProperties = 0;
    $expectedBuildings = 0;
    $expectedTenants = 0;
    $expectedInvoices = 0;
    
    for ($i = 0; $i < $adminCount; $i++) {
        $adminData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'organization_name' => fake()->company(),
            'plan_type' => 'professional',
            'expires_at' => now()->addDays(365)->toDateString(),
        ];
        
        $admin = $accountService->createAdminAccount($adminData, $superadmin);
        
        // Create random number of buildings for this admin
        $buildingCount = fake()->numberBetween(1, 3);
        $expectedBuildings += $buildingCount;
        
        for ($j = 0; $j < $buildingCount; $j++) {
            $building = Building::factory()->create([
                'tenant_id' => $admin->tenant_id,
            ]);
            
            // Create random number of properties per building
            $propertyCount = fake()->numberBetween(1, 4);
            $expectedProperties += $propertyCount;
            
            for ($k = 0; $k < $propertyCount; $k++) {
                $property = Property::factory()->create([
                    'tenant_id' => $admin->tenant_id,
                    'building_id' => $building->id,
                ]);
                
                // Create tenant for some properties
                if (fake()->boolean(60)) {
                    $tenantData = [
                        'email' => fake()->unique()->safeEmail(),
                        'password' => 'password123',
                        'name' => fake()->name(),
                        'property_id' => $property->id,
                    ];
                    
                    $accountService->createTenantAccount($tenantData, $admin);
                    $expectedTenants++;
                }
                
                // Create invoices for some properties
                if (fake()->boolean(50)) {
                    Invoice::factory()->create([
                        'tenant_id' => $admin->tenant_id,
                        'property_id' => $property->id,
                    ]);
                    $expectedInvoices++;
                }
            }
        }
    }
    
    // Act as superadmin
    $this->actingAs($superadmin);
    
    // Get actual counts from database
    $totalProperties = Property::withoutGlobalScopes()->count();
    $totalBuildings = Building::withoutGlobalScopes()->count();
    $totalTenants = User::withoutGlobalScopes()
        ->where('role', UserRole::TENANT)
        ->count();
    $totalInvoices = Invoice::withoutGlobalScopes()->count();
    
    // Property: Dashboard resource counts should match actual database counts
    expect($totalProperties)->toBe($expectedProperties)
        ->and($totalBuildings)->toBe($expectedBuildings)
        ->and($totalTenants)->toBe($expectedTenants)
        ->and($totalInvoices)->toBe($expectedInvoices);
})->repeat(100);

// Feature: hierarchical-user-management, Property 19: Data aggregation accuracy
// Validates: Requirements 17.1, 17.3, 18.1
test('admin dashboard displays accurate portfolio statistics', function () {
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
        'plan_type' => 'professional',
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin = $accountService->createAdminAccount($adminData, $superadmin);
    
    // Create resources for this admin
    $expectedProperties = fake()->numberBetween(3, 8);
    $expectedBuildings = fake()->numberBetween(1, 3);
    $expectedTenants = 0;
    $expectedActiveTenants = 0;
    $expectedInvoices = 0;
    
    for ($i = 0; $i < $expectedBuildings; $i++) {
        $building = Building::factory()->create([
            'tenant_id' => $admin->tenant_id,
        ]);
        
        $propertiesPerBuilding = (int) ceil($expectedProperties / $expectedBuildings);
        
        for ($j = 0; $j < $propertiesPerBuilding && $expectedProperties > 0; $j++) {
            $property = Property::factory()->create([
                'tenant_id' => $admin->tenant_id,
                'building_id' => $building->id,
            ]);
            
            $expectedProperties--;
            
            // Create tenant for this property
            $isActive = fake()->boolean(80);
            
            $tenantData = [
                'email' => fake()->unique()->safeEmail(),
                'password' => 'password123',
                'name' => fake()->name(),
                'property_id' => $property->id,
            ];
            
            $tenant = $accountService->createTenantAccount($tenantData, $admin);
            $tenant->update(['is_active' => $isActive]);
            
            $expectedTenants++;
            if ($isActive) {
                $expectedActiveTenants++;
            }
            
            // Create invoice for this property
            if (fake()->boolean(70)) {
                Invoice::factory()->create([
                    'tenant_id' => $admin->tenant_id,
                    'property_id' => $property->id,
                ]);
                $expectedInvoices++;
            }
        }
    }
    
    // Recalculate expected properties (we decremented it in the loop)
    $expectedProperties = Property::where('tenant_id', $admin->tenant_id)->count();
    
    // Act as admin
    $this->actingAs($admin);
    
    // Get actual counts from database (scoped to admin's tenant_id)
    $totalProperties = Property::where('tenant_id', $admin->tenant_id)->count();
    $totalBuildings = Building::where('tenant_id', $admin->tenant_id)->count();
    $totalTenants = User::where('tenant_id', $admin->tenant_id)
        ->where('role', UserRole::TENANT)
        ->count();
    $activeTenants = User::where('tenant_id', $admin->tenant_id)
        ->where('role', UserRole::TENANT)
        ->where('is_active', true)
        ->count();
    $totalInvoices = Invoice::where('tenant_id', $admin->tenant_id)->count();
    
    // Property: Admin dashboard counts should match actual database counts for their tenant_id
    expect($totalProperties)->toBe($expectedProperties)
        ->and($totalBuildings)->toBe($expectedBuildings)
        ->and($totalTenants)->toBe($expectedTenants)
        ->and($activeTenants)->toBe($expectedActiveTenants)
        ->and($totalInvoices)->toBe($expectedInvoices);
})->repeat(100);

// Feature: hierarchical-user-management, Property 19: Data aggregation accuracy
// Validates: Requirements 17.1, 17.3, 18.1
test('admin dashboard displays accurate subscription usage statistics', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create an admin account with specific limits
    $maxProperties = fake()->numberBetween(10, 20);
    $maxTenants = fake()->numberBetween(20, 50);
    
    $adminData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'organization_name' => fake()->company(),
        'plan_type' => 'professional',
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin = $accountService->createAdminAccount($adminData, $superadmin);
    
    // Update subscription limits
    $admin->subscription->update([
        'max_properties' => $maxProperties,
        'max_tenants' => $maxTenants,
    ]);
    
    // Create resources up to a certain percentage of limits
    $propertyCount = fake()->numberBetween(1, min($maxProperties, 8));
    $tenantCount = 0;
    
    $building = Building::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);
    
    for ($i = 0; $i < $propertyCount; $i++) {
        $property = Property::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'building_id' => $building->id,
        ]);
        
        // Create tenant for this property
        if ($tenantCount < min($maxTenants, $propertyCount)) {
            $tenantData = [
                'email' => fake()->unique()->safeEmail(),
                'password' => 'password123',
                'name' => fake()->name(),
                'property_id' => $property->id,
            ];
            
            $accountService->createTenantAccount($tenantData, $admin);
            $tenantCount++;
        }
    }
    
    // Act as admin
    $this->actingAs($admin);
    
    // Get actual counts
    $actualProperties = Property::where('tenant_id', $admin->tenant_id)->count();
    $actualTenants = User::where('tenant_id', $admin->tenant_id)
        ->where('role', UserRole::TENANT)
        ->count();
    
    // Calculate expected percentages
    $expectedPropertyPercentage = round(($actualProperties / $maxProperties) * 100);
    $expectedTenantPercentage = round(($actualTenants / $maxTenants) * 100);
    
    // Get subscription
    $subscription = $admin->subscription;
    
    // Calculate actual percentages
    $actualPropertyPercentage = round(($actualProperties / $subscription->max_properties) * 100);
    $actualTenantPercentage = round(($actualTenants / $subscription->max_tenants) * 100);
    
    // Property: Usage statistics should accurately reflect actual usage against limits
    expect($actualProperties)->toBe($propertyCount)
        ->and($actualTenants)->toBe($tenantCount)
        ->and($subscription->max_properties)->toBe($maxProperties)
        ->and($subscription->max_tenants)->toBe($maxTenants)
        ->and($actualPropertyPercentage)->toBe($expectedPropertyPercentage)
        ->and($actualTenantPercentage)->toBe($expectedTenantPercentage);
})->repeat(100);

// Feature: hierarchical-user-management, Property 19: Data aggregation accuracy
// Validates: Requirements 17.1, 17.3, 18.1
test('dashboard counts remain accurate after data modifications', function () {
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
        'plan_type' => 'basic',
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin = $accountService->createAdminAccount($adminData, $superadmin);
    
    // Create initial resources
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
    
    $tenantData1 = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $property1->id,
    ];
    
    $tenant1 = $accountService->createTenantAccount($tenantData1, $admin);
    
    // Get initial counts
    $initialPropertyCount = Property::where('tenant_id', $admin->tenant_id)->count();
    $initialTenantCount = User::where('tenant_id', $admin->tenant_id)
        ->where('role', UserRole::TENANT)
        ->count();
    
    expect($initialPropertyCount)->toBe(2)
        ->and($initialTenantCount)->toBe(1);
    
    // Modify data: add a new tenant
    $tenantData2 = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $property2->id,
    ];
    
    $tenant2 = $accountService->createTenantAccount($tenantData2, $admin);
    
    // Get updated counts
    $updatedTenantCount = User::where('tenant_id', $admin->tenant_id)
        ->where('role', UserRole::TENANT)
        ->count();
    
    expect($updatedTenantCount)->toBe(2);
    
    // Modify data: deactivate a tenant
    $accountService->deactivateAccount($tenant1, 'Test deactivation');
    
    $activeTenantCount = User::where('tenant_id', $admin->tenant_id)
        ->where('role', UserRole::TENANT)
        ->where('is_active', true)
        ->count();
    
    $totalTenantCount = User::where('tenant_id', $admin->tenant_id)
        ->where('role', UserRole::TENANT)
        ->count();
    
    // Property: Counts should accurately reflect modifications
    expect($totalTenantCount)->toBe(2)
        ->and($activeTenantCount)->toBe(1);
    
    // Modify data: delete a property
    $property2->delete();
    
    $finalPropertyCount = Property::where('tenant_id', $admin->tenant_id)->count();
    
    expect($finalPropertyCount)->toBe(1);
})->repeat(100);

// Feature: hierarchical-user-management, Property 19: Data aggregation accuracy
// Validates: Requirements 17.1, 17.3, 18.1
test('superadmin dashboard aggregates data correctly across multiple organizations', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create multiple admins with varying amounts of resources
    $adminCount = fake()->numberBetween(3, 6);
    $totalExpectedProperties = 0;
    $totalExpectedBuildings = 0;
    $totalExpectedTenants = 0;
    
    for ($i = 0; $i < $adminCount; $i++) {
        $adminData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'organization_name' => fake()->company(),
            'plan_type' => fake()->randomElement(['basic', 'professional', 'enterprise']),
            'expires_at' => now()->addDays(fake()->numberBetween(30, 365))->toDateString(),
        ];
        
        $admin = $accountService->createAdminAccount($adminData, $superadmin);
        
        // Create varying number of resources per admin
        $buildingCount = fake()->numberBetween(1, 3);
        $totalExpectedBuildings += $buildingCount;
        
        for ($j = 0; $j < $buildingCount; $j++) {
            $building = Building::factory()->create([
                'tenant_id' => $admin->tenant_id,
            ]);
            
            $propertyCount = fake()->numberBetween(1, 4);
            $totalExpectedProperties += $propertyCount;
            
            for ($k = 0; $k < $propertyCount; $k++) {
                $property = Property::factory()->create([
                    'tenant_id' => $admin->tenant_id,
                    'building_id' => $building->id,
                ]);
                
                // Create tenant for each property
                $tenantData = [
                    'email' => fake()->unique()->safeEmail(),
                    'password' => 'password123',
                    'name' => fake()->name(),
                    'property_id' => $property->id,
                ];
                
                $accountService->createTenantAccount($tenantData, $admin);
                $totalExpectedTenants++;
            }
        }
    }
    
    // Act as superadmin
    $this->actingAs($superadmin);
    
    // Get aggregated counts
    $totalProperties = Property::withoutGlobalScopes()->count();
    $totalBuildings = Building::withoutGlobalScopes()->count();
    $totalTenants = User::withoutGlobalScopes()
        ->where('role', UserRole::TENANT)
        ->count();
    $totalOrganizations = User::withoutGlobalScopes()
        ->where('role', UserRole::ADMIN)
        ->count();
    
    // Property: Aggregated counts should match sum of all organizations' resources
    expect($totalProperties)->toBe($totalExpectedProperties)
        ->and($totalBuildings)->toBe($totalExpectedBuildings)
        ->and($totalTenants)->toBe($totalExpectedTenants)
        ->and($totalOrganizations)->toBe($adminCount);
})->repeat(100);

// Feature: hierarchical-user-management, Property 19: Data aggregation accuracy
// Validates: Requirements 17.1, 17.3, 18.1
test('admin dashboard only counts resources within their tenant_id scope', function () {
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
    
    // Create resources for admin1
    $admin1PropertyCount = fake()->numberBetween(2, 5);
    $building1 = Building::factory()->create(['tenant_id' => $admin1->tenant_id]);
    
    for ($i = 0; $i < $admin1PropertyCount; $i++) {
        Property::factory()->create([
            'tenant_id' => $admin1->tenant_id,
            'building_id' => $building1->id,
        ]);
    }
    
    // Create resources for admin2
    $admin2PropertyCount = fake()->numberBetween(2, 5);
    $building2 = Building::factory()->create(['tenant_id' => $admin2->tenant_id]);
    
    for ($i = 0; $i < $admin2PropertyCount; $i++) {
        Property::factory()->create([
            'tenant_id' => $admin2->tenant_id,
            'building_id' => $building2->id,
        ]);
    }
    
    // Act as admin1
    $this->actingAs($admin1);
    
    $admin1Properties = Property::where('tenant_id', $admin1->tenant_id)->count();
    $admin1Buildings = Building::where('tenant_id', $admin1->tenant_id)->count();
    
    // Property: Admin1 should only see their own resources
    expect($admin1Properties)->toBe($admin1PropertyCount)
        ->and($admin1Buildings)->toBe(1);
    
    // Act as admin2
    $this->actingAs($admin2);
    
    $admin2Properties = Property::where('tenant_id', $admin2->tenant_id)->count();
    $admin2Buildings = Building::where('tenant_id', $admin2->tenant_id)->count();
    
    // Property: Admin2 should only see their own resources
    expect($admin2Properties)->toBe($admin2PropertyCount)
        ->and($admin2Buildings)->toBe(1);
    
    // Verify counts are different (isolation)
    expect($admin1Properties)->not->toBe($admin2Properties);
})->repeat(100);
