<?php

use App\Enums\UserRole;
use App\Enums\SubscriptionPlanType;
use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use App\Services\AccountManagementService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// Feature: hierarchical-user-management, Property 13: Audit logging completeness
// Validates: Requirements 1.5, 14.1, 14.2, 14.3, 14.4
test('admin account creation creates audit log entry with complete information', function () {
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
        $adminData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'organization_name' => fake()->company(),
            'plan_type' => fake()->randomElement(SubscriptionPlanType::values()),
            'expires_at' => now()->addDays(fake()->numberBetween(30, 365))->toDateString(),
        ];
        
        $admin = $accountService->createAdminAccount($adminData, $superadmin);
        
        // Property: For any admin account creation, an audit log entry should exist
        $auditLog = DB::table('user_assignments_audit')
            ->where('user_id', $admin->id)
            ->where('action', 'created')
            ->first();
        
        expect($auditLog)->not->toBeNull()
            ->and($auditLog->user_id)->toBe($admin->id)
            ->and($auditLog->action)->toBe('created')
            ->and($auditLog->performed_by)->toBe($superadmin->id)
            ->and($auditLog->created_at)->not->toBeNull();
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 13: Audit logging completeness
// Validates: Requirements 1.5, 14.1, 14.2, 14.3, 14.4
test('tenant account creation creates audit log entry with property assignment', function () {
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
        'plan_type' => SubscriptionPlanType::BASIC->value,
        'expires_at' => now()->addDays(365)->toDateString(),
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
    
    // Create a random number of tenant accounts (1-4)
    $tenantCount = fake()->numberBetween(1, 4);
    
    for ($i = 0; $i < $tenantCount; $i++) {
        $tenantData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'property_id' => $property->id,
        ];
        
        $tenant = $accountService->createTenantAccount($tenantData, $admin);
        
        // Property: For any tenant account creation, an audit log entry should exist with property assignment
        $auditLog = DB::table('user_assignments_audit')
            ->where('user_id', $tenant->id)
            ->where('action', 'created')
            ->first();
        
        expect($auditLog)->not->toBeNull()
            ->and($auditLog->user_id)->toBe($tenant->id)
            ->and($auditLog->action)->toBe('created')
            ->and($auditLog->property_id)->toBe($property->id)
            ->and($auditLog->performed_by)->toBe($admin->id)
            ->and($auditLog->created_at)->not->toBeNull();
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 13: Audit logging completeness
// Validates: Requirements 1.5, 14.1, 14.2, 14.3, 14.4
test('tenant reassignment creates audit log with old and new property', function () {
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
        'plan_type' => SubscriptionPlanType::PROFESSIONAL->value,
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin = $accountService->createAdminAccount($adminData, $superadmin);
    
    // Create two buildings and properties
    $building1 = Building::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);
    
    $property1 = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building1->id,
    ]);
    
    $building2 = Building::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);
    
    $property2 = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building2->id,
    ]);
    
    // Create a tenant assigned to property1
    $tenantData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $property1->id,
    ];
    
    $tenant = $accountService->createTenantAccount($tenantData, $admin);
    
    // Reassign tenant to property2
    $accountService->reassignTenant($tenant, $property2, $admin);
    
    // Property: For any tenant reassignment, an audit log entry should exist with both old and new property
    $reassignmentLog = DB::table('user_assignments_audit')
        ->where('user_id', $tenant->id)
        ->where('action', 'reassigned')
        ->first();
    
    expect($reassignmentLog)->not->toBeNull()
        ->and($reassignmentLog->user_id)->toBe($tenant->id)
        ->and($reassignmentLog->action)->toBe('reassigned')
        ->and($reassignmentLog->property_id)->toBe($property2->id)
        ->and($reassignmentLog->previous_property_id)->toBe($property1->id)
        ->and($reassignmentLog->performed_by)->toBe($admin->id)
        ->and($reassignmentLog->created_at)->not->toBeNull();
})->repeat(100);

// Feature: hierarchical-user-management, Property 13: Audit logging completeness
// Validates: Requirements 1.5, 14.1, 14.2, 14.3, 14.4
test('account deactivation creates audit log with reason', function () {
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
        'plan_type' => SubscriptionPlanType::BASIC->value,
        'expires_at' => now()->addDays(365)->toDateString(),
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
    
    // Create a random number of tenants (1-3)
    $tenantCount = fake()->numberBetween(1, 3);
    
    for ($i = 0; $i < $tenantCount; $i++) {
        $tenantData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'property_id' => $property->id,
        ];
        
        $tenant = $accountService->createTenantAccount($tenantData, $admin);
        
        // Authenticate as admin to perform deactivation
        $this->actingAs($admin);
        
        // Deactivate the tenant with a reason
        $reason = fake()->randomElement([
            'Tenant moved out',
            'Lease expired',
            'Non-payment',
            'Requested by tenant',
            null, // Sometimes no reason
        ]);
        
        $accountService->deactivateAccount($tenant, $reason);
        
        // Property: For any account deactivation, an audit log entry should exist with action details
        $deactivationLog = DB::table('user_assignments_audit')
            ->where('user_id', $tenant->id)
            ->where('action', 'deactivated')
            ->first();
        
        expect($deactivationLog)->not->toBeNull()
            ->and($deactivationLog->user_id)->toBe($tenant->id)
            ->and($deactivationLog->action)->toBe('deactivated')
            ->and($deactivationLog->performed_by)->toBe($admin->id)
            ->and($deactivationLog->created_at)->not->toBeNull();
        
        // If reason was provided, verify it's in the log
        if ($reason !== null) {
            expect($deactivationLog->reason)->toBe($reason);
        }
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 13: Audit logging completeness
// Validates: Requirements 1.5, 14.1, 14.2, 14.3, 14.4
test('account reactivation creates audit log entry', function () {
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
        'plan_type' => SubscriptionPlanType::BASIC->value,
        'expires_at' => now()->addDays(365)->toDateString(),
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
    
    // Create a tenant
    $tenantData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $property->id,
    ];
    
    $tenant = $accountService->createTenantAccount($tenantData, $admin);
    
    // Authenticate as admin
    $this->actingAs($admin);
    
    // Deactivate then reactivate the tenant
    $accountService->deactivateAccount($tenant, 'Test deactivation');
    $accountService->reactivateAccount($tenant);
    
    // Property: For any account reactivation, an audit log entry should exist
    $reactivationLog = DB::table('user_assignments_audit')
        ->where('user_id', $tenant->id)
        ->where('action', 'reactivated')
        ->first();
    
    expect($reactivationLog)->not->toBeNull()
        ->and($reactivationLog->user_id)->toBe($tenant->id)
        ->and($reactivationLog->action)->toBe('reactivated')
        ->and($reactivationLog->performed_by)->toBe($admin->id)
        ->and($reactivationLog->created_at)->not->toBeNull();
})->repeat(100);

// Feature: hierarchical-user-management, Property 13: Audit logging completeness
// Validates: Requirements 1.5, 14.1, 14.2, 14.3, 14.4
test('all account management actions create audit logs with timestamps', function () {
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
        'plan_type' => SubscriptionPlanType::PROFESSIONAL->value,
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin = $accountService->createAdminAccount($adminData, $superadmin);
    
    // Create two properties
    $building1 = Building::factory()->create(['tenant_id' => $admin->tenant_id]);
    $property1 = Property::factory()->create(['tenant_id' => $admin->tenant_id, 'building_id' => $building1->id]);
    
    $building2 = Building::factory()->create(['tenant_id' => $admin->tenant_id]);
    $property2 = Property::factory()->create(['tenant_id' => $admin->tenant_id, 'building_id' => $building2->id]);
    
    // Create a tenant
    $tenantData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $property1->id,
    ];
    
    $tenant = $accountService->createTenantAccount($tenantData, $admin);
    
    // Authenticate as admin
    $this->actingAs($admin);
    
    // Perform various actions
    $accountService->reassignTenant($tenant, $property2, $admin);
    $accountService->deactivateAccount($tenant, 'Testing');
    $accountService->reactivateAccount($tenant);
    
    // Property: All audit log entries should have timestamps
    $allLogs = DB::table('user_assignments_audit')
        ->where('user_id', $tenant->id)
        ->get();
    
    // Should have 4 logs: created, reassigned, deactivated, reactivated
    expect($allLogs)->toHaveCount(4);
    
    foreach ($allLogs as $log) {
        expect($log->created_at)->not->toBeNull()
            ->and($log->updated_at)->not->toBeNull()
            ->and($log->user_id)->toBe($tenant->id)
            ->and($log->performed_by)->not->toBeNull();
    }
    
    // Verify each action type exists
    $actions = $allLogs->pluck('action')->toArray();
    expect($actions)->toContain('created')
        ->and($actions)->toContain('reassigned')
        ->and($actions)->toContain('deactivated')
        ->and($actions)->toContain('reactivated');
})->repeat(100);

// Feature: hierarchical-user-management, Property 13: Audit logging completeness
// Validates: Requirements 1.5, 14.1, 14.2, 14.3, 14.4
test('audit logs maintain chronological order of actions', function () {
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
        'plan_type' => SubscriptionPlanType::BASIC->value,
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin = $accountService->createAdminAccount($adminData, $superadmin);
    
    // Create properties
    $building = Building::factory()->create(['tenant_id' => $admin->tenant_id]);
    $property1 = Property::factory()->create(['tenant_id' => $admin->tenant_id, 'building_id' => $building->id]);
    $property2 = Property::factory()->create(['tenant_id' => $admin->tenant_id, 'building_id' => $building->id]);
    
    // Create a tenant
    $tenantData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $property1->id,
    ];
    
    $tenant = $accountService->createTenantAccount($tenantData, $admin);
    
    // Authenticate as admin
    $this->actingAs($admin);
    
    // Perform actions with slight delays to ensure different timestamps
    sleep(1);
    $accountService->reassignTenant($tenant, $property2, $admin);
    sleep(1);
    $accountService->deactivateAccount($tenant);
    
    // Property: Audit logs should be in chronological order
    $logs = DB::table('user_assignments_audit')
        ->where('user_id', $tenant->id)
        ->orderBy('created_at', 'asc')
        ->get();
    
    expect($logs)->toHaveCount(3);
    
    // Verify order: created -> reassigned -> deactivated
    expect($logs[0]->action)->toBe('created')
        ->and($logs[1]->action)->toBe('reassigned')
        ->and($logs[2]->action)->toBe('deactivated');
    
    // Verify timestamps are in ascending order
    $timestamp1 = strtotime($logs[0]->created_at);
    $timestamp2 = strtotime($logs[1]->created_at);
    $timestamp3 = strtotime($logs[2]->created_at);
    
    expect($timestamp2)->toBeGreaterThanOrEqual($timestamp1)
        ->and($timestamp3)->toBeGreaterThanOrEqual($timestamp2);
})->repeat(100);

// Feature: hierarchical-user-management, Property 13: Audit logging completeness
// Validates: Requirements 1.5, 14.1, 14.2, 14.3, 14.4
test('multiple admins actions are logged with correct performer', function () {
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
        
        // Create a property for this admin
        $building = Building::factory()->create(['tenant_id' => $admin->tenant_id]);
        $property = Property::factory()->create(['tenant_id' => $admin->tenant_id, 'building_id' => $building->id]);
        
        // Create tenants for this admin
        $tenantCount = fake()->numberBetween(1, 3);
        
        for ($j = 0; $j < $tenantCount; $j++) {
            $tenantData = [
                'email' => fake()->unique()->safeEmail(),
                'password' => 'password123',
                'name' => fake()->name(),
                'property_id' => $property->id,
            ];
            
            $tenant = $accountService->createTenantAccount($tenantData, $admin);
            
            // Property: Each tenant creation should be logged with the correct admin as performer
            $tenantLog = DB::table('user_assignments_audit')
                ->where('user_id', $tenant->id)
                ->where('action', 'created')
                ->first();
            
            expect($tenantLog)->not->toBeNull()
                ->and($tenantLog->performed_by)->toBe($admin->id)
                ->and($tenantLog->user_id)->toBe($tenant->id);
        }
        
        // Property: Admin creation should be logged with superadmin as performer
        $adminLog = DB::table('user_assignments_audit')
            ->where('user_id', $admin->id)
            ->where('action', 'created')
            ->first();
        
        expect($adminLog)->not->toBeNull()
            ->and($adminLog->performed_by)->toBe($superadmin->id)
            ->and($adminLog->user_id)->toBe($admin->id);
    }
})->repeat(100);
