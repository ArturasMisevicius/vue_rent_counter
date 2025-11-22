<?php

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use App\Services\AccountManagementService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Feature: hierarchical-user-management, Property 20: Profile data completeness
// Validates: Requirements 15.1, 16.1
test('admin profile contains all required fields', function () {
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
    
    for ($i = 0; $i < $adminCount; $i++) {
        // Create an admin account with random data
        $organizationName = fake()->company();
        $email = fake()->unique()->safeEmail();
        $planType = fake()->randomElement(['basic', 'professional', 'enterprise']);
        
        $adminData = [
            'email' => $email,
            'password' => 'password123',
            'name' => fake()->name(),
            'organization_name' => $organizationName,
            'plan_type' => $planType,
            'expires_at' => now()->addDays(fake()->numberBetween(30, 365))->toDateString(),
        ];
        
        $admin = $accountService->createAdminAccount($adminData, $superadmin);
        
        // Reload admin with subscription relationship
        $admin = $admin->fresh(['subscription']);
        
        // Property: For any admin viewing their profile, the response should include 
        // organization name, contact email, and subscription status
        expect($admin->organization_name)->toBe($organizationName)
            ->and($admin->organization_name)->not->toBeNull()
            ->and($admin->email)->toBe($email)
            ->and($admin->email)->not->toBeNull()
            ->and($admin->subscription)->not->toBeNull()
            ->and($admin->subscription->status)->not->toBeNull()
            ->and($admin->subscription->plan_type)->toBe($planType)
            ->and($admin->subscription->expires_at)->not->toBeNull();
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 20: Profile data completeness
// Validates: Requirements 15.1, 16.1
test('tenant profile contains all required fields', function () {
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
    
    // Create a building and property for this admin
    $building = Building::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);
    
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building->id,
    ]);
    
    // Create a random number of tenant accounts (2-5)
    $tenantCount = fake()->numberBetween(2, 5);
    
    for ($i = 0; $i < $tenantCount; $i++) {
        $tenantEmail = fake()->unique()->safeEmail();
        
        $tenantData = [
            'email' => $tenantEmail,
            'password' => 'password123',
            'name' => fake()->name(),
            'property_id' => $property->id,
        ];
        
        $tenant = $accountService->createTenantAccount($tenantData, $admin);
        
        // Reload tenant with property and parent user relationships
        $tenant = $tenant->fresh(['property', 'parentUser']);
        
        // Property: For any tenant viewing their profile, the response should include 
        // email, assigned property, and admin contact information
        expect($tenant->email)->toBe($tenantEmail)
            ->and($tenant->email)->not->toBeNull()
            ->and($tenant->property_id)->toBe($property->id)
            ->and($tenant->property_id)->not->toBeNull()
            ->and($tenant->property)->not->toBeNull()
            ->and($tenant->property->id)->toBe($property->id)
            ->and($tenant->parentUser)->not->toBeNull()
            ->and($tenant->parentUser->id)->toBe($admin->id)
            ->and($tenant->parentUser->email)->not->toBeNull();
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 20: Profile data completeness
// Validates: Requirements 15.1, 16.1
test('admin profile subscription details are complete', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create multiple admin accounts with different subscription plans
    $planTypes = ['basic', 'professional', 'enterprise'];
    
    foreach ($planTypes as $planType) {
        $expiresAt = now()->addDays(fake()->numberBetween(30, 365));
        
        $adminData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'organization_name' => fake()->company(),
            'plan_type' => $planType,
            'expires_at' => $expiresAt->toDateString(),
        ];
        
        $admin = $accountService->createAdminAccount($adminData, $superadmin);
        
        // Reload admin with subscription
        $admin = $admin->fresh(['subscription']);
        
        // Property: Admin subscription details should show plan type, expiry date, and usage limits
        expect($admin->subscription)->not->toBeNull()
            ->and($admin->subscription->plan_type)->toBe($planType)
            ->and($admin->subscription->expires_at)->not->toBeNull()
            ->and($admin->subscription->expires_at->format('Y-m-d'))->toBe($expiresAt->format('Y-m-d'))
            ->and($admin->subscription->max_properties)->toBeGreaterThan(0)
            ->and($admin->subscription->max_tenants)->toBeGreaterThan(0)
            ->and($admin->subscription->status)->not->toBeNull();
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 20: Profile data completeness
// Validates: Requirements 15.1, 16.1
test('tenant profile includes admin contact information', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create a random number of admin accounts (2-4)
    $adminCount = fake()->numberBetween(2, 4);
    
    for ($adminIndex = 0; $adminIndex < $adminCount; $adminIndex++) {
        $adminEmail = fake()->unique()->safeEmail();
        $adminName = fake()->name();
        
        $adminData = [
            'email' => $adminEmail,
            'password' => 'password123',
            'name' => $adminName,
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
        
        for ($tenantIndex = 0; $tenantIndex < $tenantCount; $tenantIndex++) {
            $tenantData = [
                'email' => fake()->unique()->safeEmail(),
                'password' => 'password123',
                'name' => fake()->name(),
                'property_id' => $property->id,
            ];
            
            $tenant = $accountService->createTenantAccount($tenantData, $admin);
            
            // Reload tenant with parent user relationship
            $tenant = $tenant->fresh(['parentUser']);
            
            // Property: Tenant profile should display their admin's contact information
            expect($tenant->parentUser)->not->toBeNull()
                ->and($tenant->parentUser->email)->toBe($adminEmail)
                ->and($tenant->parentUser->name)->toBe($adminName)
                ->and($tenant->parentUser->role)->toBe(UserRole::ADMIN);
        }
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 20: Profile data completeness
// Validates: Requirements 15.1, 16.1
test('profile data persists correctly after database reload', function () {
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
    $organizationName = fake()->company();
    $adminEmail = fake()->unique()->safeEmail();
    
    $adminData = [
        'email' => $adminEmail,
        'password' => 'password123',
        'name' => fake()->name(),
        'organization_name' => $organizationName,
        'plan_type' => 'professional',
        'expires_at' => now()->addDays(180)->toDateString(),
    ];
    
    $admin = $accountService->createAdminAccount($adminData, $superadmin);
    $adminId = $admin->id;
    
    // Create a building and property
    $building = Building::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);
    
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building->id,
    ]);
    
    // Create a tenant account
    $tenantEmail = fake()->unique()->safeEmail();
    
    $tenantData = [
        'email' => $tenantEmail,
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $property->id,
    ];
    
    $tenant = $accountService->createTenantAccount($tenantData, $admin);
    $tenantId = $tenant->id;
    
    // Clear any cached data
    unset($admin, $tenant);
    
    // Reload admin from database
    $reloadedAdmin = User::with('subscription')->find($adminId);
    
    // Property: Admin profile data should be complete after reload
    expect($reloadedAdmin->organization_name)->toBe($organizationName)
        ->and($reloadedAdmin->email)->toBe($adminEmail)
        ->and($reloadedAdmin->subscription)->not->toBeNull()
        ->and($reloadedAdmin->subscription->plan_type)->toBe('professional');
    
    // Reload tenant from database
    $reloadedTenant = User::with(['property', 'parentUser'])->find($tenantId);
    
    // Property: Tenant profile data should be complete after reload
    expect($reloadedTenant->email)->toBe($tenantEmail)
        ->and($reloadedTenant->property_id)->toBe($property->id)
        ->and($reloadedTenant->property)->not->toBeNull()
        ->and($reloadedTenant->parentUser)->not->toBeNull()
        ->and($reloadedTenant->parentUser->email)->toBe($adminEmail);
})->repeat(100);

// Feature: hierarchical-user-management, Property 20: Profile data completeness
// Validates: Requirements 15.1, 16.1
test('all required profile fields are non-null for each role', function () {
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
    
    // Create a building and property
    $building = Building::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);
    
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building->id,
    ]);
    
    // Create a tenant account
    $tenantData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $property->id,
    ];
    
    $tenant = $accountService->createTenantAccount($tenantData, $admin);
    
    // Reload with relationships
    $admin = $admin->fresh(['subscription']);
    $tenant = $tenant->fresh(['property', 'parentUser']);
    
    // Property: Admin required fields should all be non-null
    expect($admin->email)->not->toBeNull()
        ->and($admin->organization_name)->not->toBeNull()
        ->and($admin->subscription)->not->toBeNull()
        ->and($admin->subscription->status)->not->toBeNull()
        ->and($admin->subscription->plan_type)->not->toBeNull()
        ->and($admin->subscription->expires_at)->not->toBeNull();
    
    // Property: Tenant required fields should all be non-null
    expect($tenant->email)->not->toBeNull()
        ->and($tenant->property_id)->not->toBeNull()
        ->and($tenant->property)->not->toBeNull()
        ->and($tenant->parentUser)->not->toBeNull()
        ->and($tenant->parentUser->email)->not->toBeNull();
})->repeat(100);

