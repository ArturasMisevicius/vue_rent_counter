<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\BuildingResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\MeterReadingResource;
use App\Filament\Resources\MeterResource;
use App\Filament\Resources\PropertyResource;
use App\Filament\Resources\ProviderResource;
use App\Filament\Resources\TariffResource;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Initialize the admin panel
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

/**
 * Test navigation visibility for configuration resources (Tariff, Provider).
 * Requirements 9.1, 9.2, 9.3: Configuration resources should only be visible to SUPERADMIN and ADMIN.
 */
test('configuration resources are visible only to superadmin and admin users', function () {
    $configurationResources = [
        TariffResource::class,
        ProviderResource::class,
    ];
    
    // Test SUPERADMIN - should see configuration resources
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => 1,
    ]);
    
    $this->actingAs($superadmin);
    
    foreach ($configurationResources as $resource) {
        expect($resource::shouldRegisterNavigation())
            ->toBeTrue("SUPERADMIN should see {$resource}");
    }
    
    // Test ADMIN - should see configuration resources
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    $this->actingAs($admin);
    
    foreach ($configurationResources as $resource) {
        expect($resource::shouldRegisterNavigation())
            ->toBeTrue("ADMIN should see {$resource}");
    }
    
    // Test MANAGER - should NOT see configuration resources
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
    ]);
    
    $this->actingAs($manager);
    
    foreach ($configurationResources as $resource) {
        expect($resource::shouldRegisterNavigation())
            ->toBeFalse("MANAGER should NOT see {$resource}");
    }
    
    // Test TENANT - should NOT see configuration resources
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => 1,
    ]);
    
    $this->actingAs($tenant);
    
    foreach ($configurationResources as $resource) {
        expect($resource::shouldRegisterNavigation())
            ->toBeFalse("TENANT should NOT see {$resource}");
    }
});

/**
 * Test navigation visibility for operational resources (Property, Building, Meter).
 * Requirements 9.1, 9.2: Operational resources should be visible to SUPERADMIN, ADMIN, MANAGER but not TENANT.
 */
test('operational resources are visible to superadmin, admin, and manager but not tenant', function () {
    $operationalResources = [
        PropertyResource::class,
        BuildingResource::class,
        MeterResource::class,
    ];
    
    // Test SUPERADMIN - should see operational resources
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => 1,
    ]);
    
    $this->actingAs($superadmin);
    
    foreach ($operationalResources as $resource) {
        expect($resource::shouldRegisterNavigation())
            ->toBeTrue("SUPERADMIN should see {$resource}");
    }
    
    // Test ADMIN - should see operational resources
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    $this->actingAs($admin);
    
    foreach ($operationalResources as $resource) {
        expect($resource::shouldRegisterNavigation())
            ->toBeTrue("ADMIN should see {$resource}");
    }
    
    // Test MANAGER - should see operational resources
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
    ]);
    
    $this->actingAs($manager);
    
    foreach ($operationalResources as $resource) {
        expect($resource::shouldRegisterNavigation())
            ->toBeTrue("MANAGER should see {$resource}");
    }
    
    // Test TENANT - should NOT see operational resources
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => 1,
    ]);
    
    $this->actingAs($tenant);
    
    foreach ($operationalResources as $resource) {
        expect($resource::shouldRegisterNavigation())
            ->toBeFalse("TENANT should NOT see {$resource}");
    }
});

/**
 * Test navigation visibility for tenant-accessible resources (MeterReading, Invoice).
 * Requirements 9.1: Tenant-accessible resources should be visible to all authenticated users.
 */
test('tenant-accessible resources are visible to all authenticated users', function () {
    $tenantAccessibleResources = [
        MeterReadingResource::class,
        InvoiceResource::class,
    ];
    
    $roles = [
        UserRole::SUPERADMIN,
        UserRole::ADMIN,
        UserRole::MANAGER,
        UserRole::TENANT,
    ];
    
    foreach ($roles as $role) {
        $user = User::factory()->create([
            'role' => $role,
            'tenant_id' => 1,
        ]);
        
        $this->actingAs($user);
        
        foreach ($tenantAccessibleResources as $resource) {
            expect($resource::shouldRegisterNavigation())
                ->toBeTrue("{$role->value} should see {$resource}");
        }
    }
});

/**
 * Test navigation visibility for user management resource.
 * Requirements 9.3: User management should be visible to SUPERADMIN, ADMIN, and MANAGER.
 */
test('user management resource is visible to superadmin, admin, and manager', function () {
    // Test SUPERADMIN - should see user management
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => 1,
    ]);
    
    $this->actingAs($superadmin);
    expect(UserResource::shouldRegisterNavigation())->toBeTrue('SUPERADMIN should see UserResource');
    
    // Test ADMIN - should see user management
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    $this->actingAs($admin);
    expect(UserResource::shouldRegisterNavigation())->toBeTrue('ADMIN should see UserResource');
    
    // Test MANAGER - should see user management
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
    ]);
    
    $this->actingAs($manager);
    expect(UserResource::shouldRegisterNavigation())->toBeTrue('MANAGER should see UserResource');
    
    // Test TENANT - should NOT see user management
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => 1,
    ]);
    
    $this->actingAs($tenant);
    expect(UserResource::shouldRegisterNavigation())->toBeFalse('TENANT should NOT see UserResource');
});

/**
 * Test that navigation visibility is consistent with authorization policies.
 * If a resource is visible in navigation, the user should have viewAny permission.
 */
test('navigation visibility is consistent with authorization policies', function () {
    $resources = [
        TariffResource::class,
        ProviderResource::class,
        PropertyResource::class,
        BuildingResource::class,
        MeterResource::class,
        MeterReadingResource::class,
        InvoiceResource::class,
        UserResource::class,
    ];
    
    $roles = [
        UserRole::SUPERADMIN,
        UserRole::ADMIN,
        UserRole::MANAGER,
        UserRole::TENANT,
    ];
    
    foreach ($roles as $role) {
        $user = User::factory()->create([
            'role' => $role,
            'tenant_id' => 1,
        ]);
        
        $this->actingAs($user);
        
        foreach ($resources as $resource) {
            $isVisible = $resource::shouldRegisterNavigation();
            $canViewAny = $resource::canViewAny();
            
            // If visible in navigation, user must have viewAny permission
            if ($isVisible) {
                expect($canViewAny)
                    ->toBeTrue("{$role->value} can see {$resource} in navigation but lacks viewAny permission");
            }
        }
    }
});

/**
 * Test that unauthenticated users cannot see any navigation items.
 */
test('unauthenticated users cannot see any navigation items', function () {
    $resources = [
        TariffResource::class,
        ProviderResource::class,
        PropertyResource::class,
        BuildingResource::class,
        MeterResource::class,
        MeterReadingResource::class,
        InvoiceResource::class,
        UserResource::class,
    ];
    
    // No authentication
    foreach ($resources as $resource) {
        expect($resource::shouldRegisterNavigation())
            ->toBeFalse("Unauthenticated users should NOT see {$resource}");
    }
});
