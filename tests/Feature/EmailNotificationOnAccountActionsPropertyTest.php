<?php

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use App\Notifications\TenantReassignedEmail;
use App\Notifications\WelcomeEmail;
use App\Services\AccountManagementService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

// Feature: hierarchical-user-management, Property 16: Email notification on account actions
// Validates: Requirements 5.4, 6.5
test('tenant account creation queues welcome email notification', function () {
    Notification::fake();
    
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
    
    // Create a random number of tenant accounts (1-5)
    $tenantCount = fake()->numberBetween(1, 5);
    
    for ($i = 0; $i < $tenantCount; $i++) {
        $tenantData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'property_id' => $property->id,
        ];
        
        $tenant = $accountService->createTenantAccount($tenantData, $admin);
        
        // Property: For any tenant account creation, a WelcomeEmail notification should be queued
        Notification::assertSentTo(
            $tenant,
            WelcomeEmail::class,
            function ($notification, $channels) use ($property, $tenantData) {
                // The notification should be sent via mail channel
                expect($channels)->toContain('mail');
                
                // Verify the notification contains the correct property information via toArray
                $notificationData = $notification->toArray($tenant);
                expect($notificationData['property_id'])->toBe($property->id);
                expect($notificationData['property_address'])->toBe($property->address);
                
                return true;
            }
        );
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 16: Email notification on account actions
// Validates: Requirements 5.4, 6.5
test('tenant reassignment queues reassignment email notification', function () {
    Notification::fake();
    
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
        'plan_type' => fake()->randomElement(['professional', 'enterprise']),
        'expires_at' => now()->addDays(fake()->numberBetween(30, 365))->toDateString(),
    ];
    
    $admin = $accountService->createAdminAccount($adminData, $superadmin);
    
    // Create multiple buildings and properties
    $propertyCount = fake()->numberBetween(2, 5);
    $properties = [];
    
    for ($i = 0; $i < $propertyCount; $i++) {
        $building = Building::factory()->create([
            'tenant_id' => $admin->tenant_id,
        ]);
        
        $properties[] = Property::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'building_id' => $building->id,
        ]);
    }
    
    // Create a tenant assigned to the first property
    $tenantData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $properties[0]->id,
    ];
    
    $tenant = $accountService->createTenantAccount($tenantData, $admin);
    
    // Clear notifications from tenant creation
    Notification::fake();
    
    // Reassign tenant to a different random property
    $newPropertyIndex = fake()->numberBetween(1, count($properties) - 1);
    $newProperty = $properties[$newPropertyIndex];
    $oldProperty = $properties[0];
    
    $accountService->reassignTenant($tenant, $newProperty, $admin);
    
    // Property: For any tenant reassignment, a TenantReassignedEmail notification should be queued
    Notification::assertSentTo(
        $tenant,
        TenantReassignedEmail::class,
        function ($notification, $channels) use ($newProperty, $oldProperty) {
            // The notification should be sent via mail channel
            expect($channels)->toContain('mail');
            
            // Verify the notification contains the correct property information via toArray
            $notificationData = $notification->toArray($tenant);
            expect($notificationData['new_property_id'])->toBe($newProperty->id);
            expect($notificationData['previous_property_id'])->toBe($oldProperty->id);
            
            return true;
        }
    );
})->repeat(100);

// Feature: hierarchical-user-management, Property 16: Email notification on account actions
// Validates: Requirements 5.4, 6.5
test('multiple tenant creations queue separate welcome email notifications', function () {
    Notification::fake();
    
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
    
    // Create multiple properties
    $propertyCount = fake()->numberBetween(2, 4);
    $properties = [];
    
    for ($i = 0; $i < $propertyCount; $i++) {
        $building = Building::factory()->create(['tenant_id' => $admin->tenant_id]);
        $properties[] = Property::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'building_id' => $building->id,
        ]);
    }
    
    // Create multiple tenants
    $tenantCount = fake()->numberBetween(2, 5);
    $tenants = [];
    
    for ($i = 0; $i < $tenantCount; $i++) {
        $property = $properties[fake()->numberBetween(0, count($properties) - 1)];
        
        $tenantData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'property_id' => $property->id,
        ];
        
        $tenants[] = $accountService->createTenantAccount($tenantData, $admin);
    }
    
    // Property: Each tenant should receive exactly one WelcomeEmail notification
    foreach ($tenants as $tenant) {
        Notification::assertSentTo($tenant, WelcomeEmail::class);
    }
    
    // Property: Total number of notifications should equal number of tenants created
    Notification::assertSentTimes(WelcomeEmail::class, $tenantCount);
})->repeat(100);

// Feature: hierarchical-user-management, Property 16: Email notification on account actions
// Validates: Requirements 5.4, 6.5
test('tenant reassignment to same property still queues notification', function () {
    Notification::fake();
    
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
    
    // Create a building and property
    $building = Building::factory()->create(['tenant_id' => $admin->tenant_id]);
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building->id,
    ]);
    
    // Create a tenant
    $tenantData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $property->id,
    ];
    
    $tenant = $accountService->createTenantAccount($tenantData, $admin);
    
    // Clear notifications from tenant creation
    Notification::fake();
    
    // Reassign tenant to the same property (edge case)
    $accountService->reassignTenant($tenant, $property, $admin);
    
    // Property: Even when reassigning to the same property, a notification should be queued
    Notification::assertSentTo($tenant, TenantReassignedEmail::class);
})->repeat(100);

// Feature: hierarchical-user-management, Property 16: Email notification on account actions
// Validates: Requirements 5.4, 6.5
test('email notifications are queued not sent immediately', function () {
    Notification::fake();
    
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
    
    // Create a building and property
    $building = Building::factory()->create(['tenant_id' => $admin->tenant_id]);
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building->id,
    ]);
    
    // Create a tenant
    $tenantData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $property->id,
    ];
    
    $tenant = $accountService->createTenantAccount($tenantData, $admin);
    
    // Property: Notifications should implement ShouldQueue interface (verified by checking the notification class)
    // This ensures notifications are queued for background processing
    $welcomeNotification = new WelcomeEmail($property, $tenantData['password']);
    expect($welcomeNotification)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
    
    // Clear and test reassignment
    Notification::fake();
    
    $building2 = Building::factory()->create(['tenant_id' => $admin->tenant_id]);
    $property2 = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building2->id,
    ]);
    
    $accountService->reassignTenant($tenant, $property2, $admin);
    
    $reassignNotification = new TenantReassignedEmail($property2, $property);
    expect($reassignNotification)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
})->repeat(100);

// Feature: hierarchical-user-management, Property 16: Email notification on account actions
// Validates: Requirements 5.4, 6.5
test('no email notifications are sent for account deactivation or reactivation', function () {
    Notification::fake();
    
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
    
    // Create a building and property
    $building = Building::factory()->create(['tenant_id' => $admin->tenant_id]);
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building->id,
    ]);
    
    // Create a tenant
    $tenantData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $property->id,
    ];
    
    $tenant = $accountService->createTenantAccount($tenantData, $admin);
    
    // Clear notifications from tenant creation
    Notification::fake();
    
    // Authenticate as admin
    $this->actingAs($admin);
    
    // Deactivate the tenant
    $accountService->deactivateAccount($tenant, 'Test deactivation');
    
    // Property: No notifications should be sent for deactivation
    Notification::assertNothingSent();
    
    // Reactivate the tenant
    $accountService->reactivateAccount($tenant);
    
    // Property: No notifications should be sent for reactivation
    Notification::assertNothingSent();
})->repeat(100);
