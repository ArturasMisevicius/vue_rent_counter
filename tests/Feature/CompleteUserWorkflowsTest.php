<?php

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AccountManagementService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
});

describe('Complete User Workflows - Manual Testing Scenarios', function () {
    
    test('workflow 1: superadmin creates admin account with subscription', function () {
        // Step 1: Create superadmin
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'tenant_id' => null,
            'email' => 'superadmin@example.com',
            'is_active' => true,
        ]);
        
        // Step 2: Authenticate as superadmin
        $this->actingAs($superadmin);
        
        // Step 3: Create admin account with subscription
        $accountService = app(AccountManagementService::class);
        
        $adminData = [
            'email' => 'admin@testorg.com',
            'password' => 'SecurePassword123',
            'name' => 'Test Admin',
            'organization_name' => 'Test Organization',
            'plan_type' => 'professional',
            'expires_at' => now()->addYear()->toDateString(),
        ];
        
        $admin = $accountService->createAdminAccount($adminData, $superadmin);
        
        // Verify admin was created correctly
        expect($admin)->toBeInstanceOf(User::class)
            ->and($admin->role)->toBe(UserRole::ADMIN)
            ->and($admin->tenant_id)->not->toBeNull()
            ->and($admin->organization_name)->toBe('Test Organization')
            ->and($admin->is_active)->toBeTrue();
        
        // Verify subscription was created
        $subscription = $admin->subscription;
        expect($subscription)->not->toBeNull()
            ->and($subscription->plan_type)->toBe('professional')
            ->and($subscription->status)->toBe('active')
            ->and($subscription->isActive())->toBeTrue();
        
        // Verify audit log was created
        $auditLog = \DB::table('user_assignments_audit')
            ->where('user_id', $admin->id)
            ->where('action', 'created')
            ->first();
        
        expect($auditLog)->not->toBeNull()
            ->and($auditLog->performed_by)->toBe($superadmin->id);
        
        // Step 4: Verify admin can login
        $this->post('/login', [
            'email' => 'admin@testorg.com',
            'password' => 'SecurePassword123',
        ]);
        
        $this->assertAuthenticated();
        expect(auth()->user()->id)->toBe($admin->id);
    })->group('workflow', 'manual-testing');
    
    test('workflow 2: admin creates tenant accounts and assigns to properties', function () {
        // Step 1: Setup - Create admin with subscription
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1000,
            'organization_name' => 'Test Organization',
            'is_active' => true,
        ]);
        
        Subscription::factory()->create([
            'user_id' => $admin->id,
            'plan_type' => 'professional',
            'status' => 'active',
            'expires_at' => now()->addYear(),
            'max_properties' => 50,
            'max_tenants' => 200,
        ]);
        
        // Step 2: Create building and properties
        $building = Building::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'name' => 'Test Building',
        ]);
        
        $property1 = Property::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'building_id' => $building->id,
            'unit_number' => 'Apt 101',
        ]);
        
        $property2 = Property::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'building_id' => $building->id,
            'unit_number' => 'Apt 102',
        ]);
        
        // Step 3: Authenticate as admin
        $this->actingAs($admin);
        
        // Step 4: Create tenant account for property 1
        $accountService = app(AccountManagementService::class);
        
        $tenantData = [
            'email' => 'tenant1@example.com',
            'password' => 'TenantPass123',
            'name' => 'John Tenant',
            'property_id' => $property1->id,
        ];
        
        $tenant1 = $accountService->createTenantAccount($tenantData, $admin);
        
        // Verify tenant was created correctly
        expect($tenant1)->toBeInstanceOf(User::class)
            ->and($tenant1->role)->toBe(UserRole::TENANT)
            ->and($tenant1->tenant_id)->toBe($admin->tenant_id)
            ->and($tenant1->property_id)->toBe($property1->id)
            ->and($tenant1->parent_user_id)->toBe($admin->id)
            ->and($tenant1->is_active)->toBeTrue();
        
        // Step 5: Create second tenant for property 2
        $tenant2Data = [
            'email' => 'tenant2@example.com',
            'password' => 'TenantPass456',
            'name' => 'Jane Tenant',
            'property_id' => $property2->id,
        ];
        
        $tenant2 = $accountService->createTenantAccount($tenant2Data, $admin);
        
        expect($tenant2->property_id)->toBe($property2->id)
            ->and($tenant2->tenant_id)->toBe($admin->tenant_id);
        
        // Step 6: Verify tenants can login
        $this->post('/login', [
            'email' => 'tenant1@example.com',
            'password' => 'TenantPass123',
        ]);
        
        $this->assertAuthenticated();
        expect(auth()->user()->id)->toBe($tenant1->id);
    })->group('workflow', 'manual-testing');
    
    test('workflow 3: tenant login and data access restrictions', function () {
        // Step 1: Setup - Create admin, properties, and tenants
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1000,
            'is_active' => true,
        ]);
        
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
        
        $tenant1 = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $admin->tenant_id,
            'property_id' => $property1->id,
            'parent_user_id' => $admin->id,
            'email' => 'tenant1@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        
        $tenant2 = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $admin->tenant_id,
            'property_id' => $property2->id,
            'parent_user_id' => $admin->id,
            'email' => 'tenant2@example.com',
            'is_active' => true,
        ]);
        
        // Step 2: Login as tenant1
        $this->actingAs($tenant1);
        
        // Step 3: Verify tenant can only see their assigned property
        $visibleProperties = Property::all();
        expect($visibleProperties)->toHaveCount(1)
            ->and($visibleProperties->first()->id)->toBe($property1->id);
        
        // Step 4: Verify tenant cannot see other tenant's property
        $property2Attempt = Property::find($property2->id);
        expect($property2Attempt)->toBeNull();
        
        // Step 5: Verify tenant can see their building
        $visibleBuildings = Building::all();
        expect($visibleBuildings)->toHaveCount(1)
            ->and($visibleBuildings->first()->id)->toBe($building->id);
        
        // Step 6: Switch to tenant2 and verify isolation
        $this->actingAs($tenant2);
        
        $visibleProperties = Property::all();
        expect($visibleProperties)->toHaveCount(1)
            ->and($visibleProperties->first()->id)->toBe($property2->id);
        
        // Tenant2 cannot see property1
        $property1Attempt = Property::find($property1->id);
        expect($property1Attempt)->toBeNull();
    })->group('workflow', 'manual-testing');
    
    test('workflow 4: subscription expiry and renewal flows', function () {
        // Step 1: Create admin with active subscription
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1000,
            'is_active' => true,
        ]);
        
        $subscription = Subscription::factory()->create([
            'user_id' => $admin->id,
            'plan_type' => 'basic',
            'status' => 'active',
            'expires_at' => now()->addDays(5), // Expires soon
            'max_properties' => 10,
            'max_tenants' => 50,
        ]);
        
        // Step 2: Verify subscription is active
        $subscriptionService = app(SubscriptionService::class);
        $status = $subscriptionService->checkSubscriptionStatus($admin);
        
        expect($status['is_active'])->toBeTrue()
            ->and($status['days_until_expiry'])->toBe(5);
        
        // Step 3: Expire the subscription
        $subscription->update([
            'expires_at' => now()->subDay(),
            'status' => 'expired',
        ]);
        
        $subscription = $subscription->fresh();
        
        // Step 4: Verify subscription is expired
        expect($subscription->isExpired())->toBeTrue()
            ->and($subscription->isActive())->toBeFalse();
        
        $status = $subscriptionService->checkSubscriptionStatus($admin);
        expect($status['is_active'])->toBeFalse();
        
        // Step 5: Attempt to create property with expired subscription
        $this->actingAs($admin);
        
        try {
            $subscriptionService->enforceSubscriptionLimits($admin, 'property');
            $this->fail('Expected SubscriptionExpiredException to be thrown');
        } catch (\App\Exceptions\SubscriptionExpiredException $e) {
            expect($e->getMessage())->toContain('expired');
        }
        
        // Step 6: Renew subscription
        $newExpiryDate = now()->addYear();
        $renewedSubscription = $subscriptionService->renewSubscription($subscription, $newExpiryDate);
        
        // Step 7: Verify subscription is active again
        expect($renewedSubscription->status)->toBe('active')
            ->and($renewedSubscription->isActive())->toBeTrue()
            ->and($renewedSubscription->expires_at->format('Y-m-d'))->toBe($newExpiryDate->format('Y-m-d'));
        
        // Step 8: Verify admin can now create resources
        $subscriptionService->enforceSubscriptionLimits($admin, 'property');
        // No exception thrown - success!
        
        expect(true)->toBeTrue(); // Test passes if we reach here
    })->group('workflow', 'manual-testing');
    
    test('workflow 5: account deactivation and reactivation', function () {
        // Step 1: Create admin and tenant
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1000,
            'is_active' => true,
        ]);
        
        $property = Property::factory()->create([
            'tenant_id' => $admin->tenant_id,
        ]);
        
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $admin->tenant_id,
            'property_id' => $property->id,
            'parent_user_id' => $admin->id,
            'email' => 'tenant@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        
        // Step 2: Verify tenant is active and can login
        expect($tenant->is_active)->toBeTrue();
        
        $this->post('/login', [
            'email' => 'tenant@example.com',
            'password' => 'password',
        ]);
        
        $this->assertAuthenticated();
        
        // Step 3: Admin deactivates tenant account
        $this->actingAs($admin);
        
        $accountService = app(AccountManagementService::class);
        $accountService->deactivateAccount($tenant, 'Tenant moved out');
        
        $tenant = $tenant->fresh();
        
        // Step 4: Verify tenant is deactivated
        expect($tenant->is_active)->toBeFalse();
        
        // Verify audit log
        $auditLog = \DB::table('user_assignments_audit')
            ->where('user_id', $tenant->id)
            ->where('action', 'deactivated')
            ->first();
        
        expect($auditLog)->not->toBeNull()
            ->and($auditLog->reason)->toBe('Tenant moved out')
            ->and($auditLog->performed_by)->toBe($admin->id);
        
        // Step 5: Attempt to login as deactivated tenant
        auth()->logout();
        
        $response = $this->post('/login', [
            'email' => 'tenant@example.com',
            'password' => 'password',
        ]);
        
        // Should not be authenticated (middleware blocks inactive users)
        $this->assertGuest();
        
        // Step 6: Admin reactivates tenant account
        $this->actingAs($admin);
        $accountService->reactivateAccount($tenant);
        
        $tenant = $tenant->fresh();
        
        // Step 7: Verify tenant is reactivated
        expect($tenant->is_active)->toBeTrue();
        
        // Verify audit log
        $auditLog = \DB::table('user_assignments_audit')
            ->where('user_id', $tenant->id)
            ->where('action', 'reactivated')
            ->first();
        
        expect($auditLog)->not->toBeNull()
            ->and($auditLog->performed_by)->toBe($admin->id);
        
        // Step 8: Verify tenant can login again
        auth()->logout();
        
        $this->post('/login', [
            'email' => 'tenant@example.com',
            'password' => 'password',
        ]);
        
        $this->assertAuthenticated();
        expect(auth()->user()->id)->toBe($tenant->id);
    })->group('workflow', 'manual-testing');
    
    test('workflow 6: tenant reassignment between properties', function () {
        // Step 1: Setup - Create admin with two properties
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1000,
            'is_active' => true,
        ]);
        
        $building = Building::factory()->create([
            'tenant_id' => $admin->tenant_id,
        ]);
        
        $property1 = Property::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'building_id' => $building->id,
            'unit_number' => 'Apt 101',
        ]);
        
        $property2 = Property::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'building_id' => $building->id,
            'unit_number' => 'Apt 102',
        ]);
        
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $admin->tenant_id,
            'property_id' => $property1->id,
            'parent_user_id' => $admin->id,
            'is_active' => true,
        ]);
        
        // Step 2: Verify tenant is assigned to property1
        expect($tenant->property_id)->toBe($property1->id);
        
        // Step 3: Admin reassigns tenant to property2
        $this->actingAs($admin);
        
        $accountService = app(AccountManagementService::class);
        $accountService->reassignTenant($tenant, $property2, $admin);
        
        $tenant = $tenant->fresh();
        
        // Step 4: Verify tenant is now assigned to property2
        expect($tenant->property_id)->toBe($property2->id);
        
        // Step 5: Verify audit log records the reassignment
        $auditLog = \DB::table('user_assignments_audit')
            ->where('user_id', $tenant->id)
            ->where('action', 'reassigned')
            ->first();
        
        expect($auditLog)->not->toBeNull()
            ->and($auditLog->property_id)->toBe($property2->id)
            ->and($auditLog->previous_property_id)->toBe($property1->id)
            ->and($auditLog->performed_by)->toBe($admin->id);
        
        // Step 6: Verify tenant can only see property2 now
        $this->actingAs($tenant);
        
        $visibleProperties = Property::all();
        expect($visibleProperties)->toHaveCount(1)
            ->and($visibleProperties->first()->id)->toBe($property2->id);
    })->group('workflow', 'manual-testing');
    
    test('workflow 7: subscription limits enforcement', function () {
        // Step 1: Create admin with basic subscription (limited)
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1000,
            'is_active' => true,
        ]);
        
        $subscription = Subscription::factory()->create([
            'user_id' => $admin->id,
            'plan_type' => 'basic',
            'status' => 'active',
            'expires_at' => now()->addYear(),
            'max_properties' => 2,
            'max_tenants' => 3,
        ]);
        
        $building = Building::factory()->create([
            'tenant_id' => $admin->tenant_id,
        ]);
        
        // Step 2: Create properties up to the limit
        $property1 = Property::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'building_id' => $building->id,
        ]);
        
        $property2 = Property::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'building_id' => $building->id,
        ]);
        
        // Step 3: Verify we're at the property limit
        $subscriptionService = app(SubscriptionService::class);
        $status = $subscriptionService->checkSubscriptionStatus($admin);
        
        expect($status['current_properties'])->toBe(2)
            ->and($status['max_properties'])->toBe(2)
            ->and($status['can_add_property'])->toBeFalse();
        
        // Step 4: Attempt to create another property (should fail)
        $this->actingAs($admin);
        
        try {
            $subscriptionService->enforceSubscriptionLimits($admin, 'property');
            $this->fail('Expected SubscriptionLimitExceededException to be thrown');
        } catch (\App\Exceptions\SubscriptionLimitExceededException $e) {
            expect($e->getMessage())->toContain('maximum number of properties');
        }
        
        // Step 5: Create tenants up to the limit
        $accountService = app(AccountManagementService::class);
        
        for ($i = 1; $i <= 3; $i++) {
            $tenant = $accountService->createTenantAccount([
                'email' => "tenant{$i}@example.com",
                'password' => 'password',
                'name' => "Tenant {$i}",
                'property_id' => $property1->id,
            ], $admin);
            
            expect($tenant)->toBeInstanceOf(User::class);
        }
        
        // Step 6: Verify we're at the tenant limit
        $status = $subscriptionService->checkSubscriptionStatus($admin);
        
        expect($status['current_tenants'])->toBe(3)
            ->and($status['max_tenants'])->toBe(3)
            ->and($status['can_add_tenant'])->toBeFalse();
        
        // Step 7: Attempt to create another tenant (should fail)
        try {
            $accountService->createTenantAccount([
                'email' => 'tenant4@example.com',
                'password' => 'password',
                'name' => 'Tenant 4',
                'property_id' => $property1->id,
            ], $admin);
            
            $this->fail('Expected SubscriptionLimitExceededException to be thrown');
        } catch (\App\Exceptions\SubscriptionLimitExceededException $e) {
            expect($e->getMessage())->toContain('maximum number of tenants');
        }
    })->group('workflow', 'manual-testing');
    
    test('workflow 8: cross-tenant access prevention', function () {
        // Step 1: Create two separate admin organizations
        $admin1 = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1000,
            'organization_name' => 'Organization 1',
            'is_active' => true,
        ]);
        
        $admin2 = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 2000,
            'organization_name' => 'Organization 2',
            'is_active' => true,
        ]);
        
        // Step 2: Create properties for each admin
        $building1 = Building::factory()->create([
            'tenant_id' => $admin1->tenant_id,
        ]);
        
        $property1 = Property::factory()->create([
            'tenant_id' => $admin1->tenant_id,
            'building_id' => $building1->id,
        ]);
        
        $building2 = Building::factory()->create([
            'tenant_id' => $admin2->tenant_id,
        ]);
        
        $property2 = Property::factory()->create([
            'tenant_id' => $admin2->tenant_id,
            'building_id' => $building2->id,
        ]);
        
        // Step 3: Login as admin1
        $this->actingAs($admin1);
        
        // Step 4: Verify admin1 can only see their own properties
        $visibleProperties = Property::all();
        expect($visibleProperties)->toHaveCount(1)
            ->and($visibleProperties->first()->id)->toBe($property1->id);
        
        // Step 5: Verify admin1 cannot access admin2's property
        $property2Attempt = Property::find($property2->id);
        expect($property2Attempt)->toBeNull();
        
        // Step 6: Switch to admin2
        $this->actingAs($admin2);
        
        // Step 7: Verify admin2 can only see their own properties
        $visibleProperties = Property::all();
        expect($visibleProperties)->toHaveCount(1)
            ->and($visibleProperties->first()->id)->toBe($property2->id);
        
        // Step 8: Verify admin2 cannot access admin1's property
        $property1Attempt = Property::find($property1->id);
        expect($property1Attempt)->toBeNull();
    })->group('workflow', 'manual-testing');
});
