<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Services\AccountManagementService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Feature: hierarchical-user-management, Property 4: Unique tenant_id assignment
// Validates: Requirements 2.2, 3.2
test('each admin account receives a unique tenant_id', function () {
    // Create a superadmin to perform the account creation
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the account management service
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Generate a random number of admin accounts to create (between 2 and 10)
    $adminCount = fake()->numberBetween(2, 10);
    $createdAdmins = [];
    $tenantIds = [];
    
    // Create multiple admin accounts
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
        $createdAdmins[] = $admin;
        $tenantIds[] = $admin->tenant_id;
    }
    
    // Property: For any two different admin accounts, their tenant_id values should be unique
    // Verify all tenant_ids are unique by checking array uniqueness
    $uniqueTenantIds = array_unique($tenantIds);
    expect(count($uniqueTenantIds))->toBe(count($tenantIds))
        ->and($tenantIds)->toHaveCount($adminCount);
    
    // Additional verification: Check each pair of admins has different tenant_ids
    for ($i = 0; $i < count($createdAdmins); $i++) {
        for ($j = $i + 1; $j < count($createdAdmins); $j++) {
            expect($createdAdmins[$i]->tenant_id)
                ->not->toBe($createdAdmins[$j]->tenant_id);
        }
    }
    
    // Verify all tenant_ids are not null
    foreach ($createdAdmins as $admin) {
        expect($admin->tenant_id)->not->toBeNull();
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 4: Unique tenant_id assignment
// Validates: Requirements 2.2, 3.2
test('tenant_id uniqueness is maintained across database queries', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the account management service
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create a random number of admin accounts
    $adminCount = fake()->numberBetween(3, 8);
    
    for ($i = 0; $i < $adminCount; $i++) {
        $adminData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'organization_name' => fake()->company(),
            'plan_type' => fake()->randomElement(['basic', 'professional', 'enterprise']),
            'expires_at' => now()->addDays(fake()->numberBetween(30, 365))->toDateString(),
        ];
        
        $accountService->createAdminAccount($adminData, $superadmin);
    }
    
    // Property: Query all admin users and verify tenant_ids are unique
    $admins = User::where('role', UserRole::ADMIN)->get();
    $tenantIds = $admins->pluck('tenant_id')->toArray();
    
    // Verify uniqueness
    $uniqueTenantIds = array_unique($tenantIds);
    expect(count($uniqueTenantIds))->toBe(count($tenantIds))
        ->and($admins)->toHaveCount($adminCount);
    
    // Verify no duplicate tenant_ids exist in the database
    $duplicates = User::where('role', UserRole::ADMIN)
        ->selectRaw('tenant_id, COUNT(*) as count')
        ->groupBy('tenant_id')
        ->having('count', '>', 1)
        ->get();
    
    expect($duplicates)->toBeEmpty();
})->repeat(100);

// Feature: hierarchical-user-management, Property 4: Unique tenant_id assignment
// Validates: Requirements 2.2, 3.2
test('tenant_id is assigned and persisted correctly for each admin', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the account management service
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create a random number of admin accounts
    $adminCount = fake()->numberBetween(2, 6);
    $adminIds = [];
    
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
        $adminIds[] = $admin->id;
    }
    
    // Property: Reload each admin from database and verify tenant_id is persisted and unique
    $reloadedAdmins = User::whereIn('id', $adminIds)->get();
    $tenantIds = $reloadedAdmins->pluck('tenant_id')->toArray();
    
    // Verify all tenant_ids are set (not null)
    foreach ($tenantIds as $tenantId) {
        expect($tenantId)->not->toBeNull();
    }
    
    // Verify all tenant_ids are unique
    $uniqueTenantIds = array_unique($tenantIds);
    expect(count($uniqueTenantIds))->toBe(count($tenantIds));
})->repeat(100);

// Feature: hierarchical-user-management, Property 4: Unique tenant_id assignment
// Validates: Requirements 2.2, 3.2
test('tenant_id uniqueness holds even with concurrent-like creation patterns', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the account management service
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Simulate rapid creation of admin accounts (like concurrent requests)
    $adminCount = fake()->numberBetween(5, 15);
    $createdTenantIds = [];
    
    for ($i = 0; $i < $adminCount; $i++) {
        $adminData = [
            'email' => "admin{$i}_" . fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'organization_name' => fake()->company(),
            'plan_type' => fake()->randomElement(['basic', 'professional', 'enterprise']),
            'expires_at' => now()->addDays(fake()->numberBetween(30, 365))->toDateString(),
        ];
        
        $admin = $accountService->createAdminAccount($adminData, $superadmin);
        
        // Verify tenant_id is not null
        expect($admin->tenant_id)->not->toBeNull();
        
        // Verify this tenant_id hasn't been used before
        expect($createdTenantIds)->not->toContain($admin->tenant_id);
        
        $createdTenantIds[] = $admin->tenant_id;
    }
    
    // Property: All created tenant_ids should be unique
    expect(count(array_unique($createdTenantIds)))->toBe($adminCount);
})->repeat(100);

// Feature: hierarchical-user-management, Property 4: Unique tenant_id assignment
// Validates: Requirements 2.2, 3.2
test('superadmin has null tenant_id while admins have unique non-null tenant_ids', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Verify superadmin has null tenant_id
    expect($superadmin->tenant_id)->toBeNull();
    
    // Create the account management service
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create random number of admin accounts
    $adminCount = fake()->numberBetween(2, 8);
    $adminTenantIds = [];
    
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
        
        // Property: Each admin should have a non-null tenant_id
        expect($admin->tenant_id)->not->toBeNull();
        
        $adminTenantIds[] = $admin->tenant_id;
    }
    
    // Property: All admin tenant_ids should be unique
    expect(count(array_unique($adminTenantIds)))->toBe($adminCount);
    
    // Property: No admin should have the same tenant_id as superadmin (null)
    foreach ($adminTenantIds as $tenantId) {
        expect($tenantId)->not->toBeNull();
    }
})->repeat(100);
