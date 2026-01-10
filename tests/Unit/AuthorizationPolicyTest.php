<?php

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Enums\ValidationStatus;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Helper function to authenticate a user and check permission.
 * This ensures the TenantBoundaryService has proper context.
 */
function checkPermission(User $user, string $ability, mixed $model = null): bool
{
    // Authenticate the user to set up proper context
    auth()->login($user);
    
    $result = $model !== null 
        ? $user->can($ability, $model) 
        : $user->can($ability);
    
    auth()->logout();
    
    return $result;
}

describe('TariffPolicy', function () {
    test('only admins can view any tariffs', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('viewAny', Tariff::class))->toBeTrue()
            ->and($manager->can('viewAny', Tariff::class))->toBeFalse()
            ->and($tenant->can('viewAny', Tariff::class))->toBeFalse();
    });

    test('only admins can view individual tariffs', function () {
        $tariff = Tariff::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('view', $tariff))->toBeTrue()
            ->and($manager->can('view', $tariff))->toBeFalse()
            ->and($tenant->can('view', $tariff))->toBeFalse();
    });

    test('only admins can create tariffs', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('create', Tariff::class))->toBeTrue()
            ->and($manager->can('create', Tariff::class))->toBeFalse()
            ->and($tenant->can('create', Tariff::class))->toBeFalse();
    });

    test('only admins can update tariffs', function () {
        $tariff = Tariff::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('update', $tariff))->toBeTrue()
            ->and($manager->can('update', $tariff))->toBeFalse()
            ->and($tenant->can('update', $tariff))->toBeFalse();
    });

    test('only admins can delete tariffs', function () {
        $tariff = Tariff::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('delete', $tariff))->toBeTrue()
            ->and($manager->can('delete', $tariff))->toBeFalse()
            ->and($tenant->can('delete', $tariff))->toBeFalse();
    });
});

describe('InvoicePolicy', function () {
    test('all users can view any invoices', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('viewAny', Invoice::class))->toBeTrue()
            ->and($manager->can('viewAny', Invoice::class))->toBeTrue()
            ->and($tenant->can('viewAny', Invoice::class))->toBeTrue();
    });

    test('admins and managers can view all invoices within their tenant', function () {
        $invoice = Invoice::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $invoice->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $invoice->tenant_id]);

        // Authenticate to set up tenant context
        expect(checkPermission($admin, 'view', $invoice))->toBeTrue()
            ->and(checkPermission($manager, 'view', $invoice))->toBeTrue();
    });

    test('tenants can only view their own invoices', function () {
        $property = Property::factory()->create();
        $tenant = Tenant::factory()->create(['property_id' => $property->id]);
        $invoice = Invoice::factory()->create(['tenant_renter_id' => $tenant->id, 'tenant_id' => $property->tenant_id]);
        
        $tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'email' => $tenant->email,
            'tenant_id' => $property->tenant_id,
        ]);

        expect(checkPermission($tenantUser, 'view', $invoice))->toBeTrue();
    });

    test('tenants cannot view other tenants invoices', function () {
        $property1 = Property::factory()->create();
        $tenant1 = Tenant::factory()->create(['property_id' => $property1->id]);
        $invoice1 = Invoice::factory()->create(['tenant_renter_id' => $tenant1->id, 'tenant_id' => $property1->tenant_id]);
        
        $property2 = Property::factory()->create(['tenant_id' => $property1->tenant_id]);
        $tenant2 = Tenant::factory()->create(['property_id' => $property2->id]);
        
        $tenantUser2 = User::factory()->create([
            'role' => UserRole::TENANT,
            'email' => $tenant2->email,
            'tenant_id' => $property1->tenant_id,
        ]);

        expect(checkPermission($tenantUser2, 'view', $invoice1))->toBeFalse();
    });

    test('admins and managers can create invoices within their tenant', function () {
        $tenantId = 1;
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $tenantId]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $tenantId]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => $tenantId]);

        expect(checkPermission($admin, 'create', Invoice::class))->toBeTrue()
            ->and(checkPermission($manager, 'create', Invoice::class))->toBeTrue()
            ->and(checkPermission($tenant, 'create', Invoice::class))->toBeFalse();
    });

    test('admins and managers can update draft invoices within their tenant', function () {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $invoice->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $invoice->tenant_id]);

        expect(checkPermission($admin, 'update', $invoice))->toBeTrue()
            ->and(checkPermission($manager, 'update', $invoice))->toBeTrue();
    });

    test('finalized invoices cannot be updated', function () {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::FINALIZED]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $invoice->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $invoice->tenant_id]);

        expect(checkPermission($admin, 'update', $invoice))->toBeFalse()
            ->and(checkPermission($manager, 'update', $invoice))->toBeFalse();
    });

    test('admins and managers can finalize draft invoices within their tenant', function () {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $invoice->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $invoice->tenant_id]);

        expect(checkPermission($admin, 'finalize', $invoice))->toBeTrue()
            ->and(checkPermission($manager, 'finalize', $invoice))->toBeTrue();
    });

    test('finalized invoices cannot be finalized again', function () {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::FINALIZED]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $invoice->tenant_id]);

        expect(checkPermission($admin, 'finalize', $invoice))->toBeFalse();
    });

    test('admins and managers can delete draft invoices within their tenant', function () {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $invoice->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $invoice->tenant_id]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => $invoice->tenant_id]);

        // Note: The policy allows managers to delete draft invoices (permissive workflow)
        expect(checkPermission($admin, 'delete', $invoice))->toBeTrue()
            ->and(checkPermission($manager, 'delete', $invoice))->toBeTrue()
            ->and(checkPermission($tenant, 'delete', $invoice))->toBeFalse();
    });

    test('finalized invoices cannot be deleted', function () {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::FINALIZED]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $invoice->tenant_id]);

        expect(checkPermission($admin, 'delete', $invoice))->toBeFalse();
    });
});

describe('MeterReadingPolicy', function () {
    test('admins and managers can view any meter readings within their tenant', function () {
        $tenantId = 1;
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $tenantId]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $tenantId]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => $tenantId]);

        // MeterReadingPolicy.viewAny requires manager operations AND tenant context
        expect(checkPermission($admin, 'viewAny', MeterReading::class))->toBeTrue()
            ->and(checkPermission($manager, 'viewAny', MeterReading::class))->toBeTrue()
            ->and(checkPermission($tenant, 'viewAny', MeterReading::class))->toBeFalse();
    });

    test('admins and managers can view all meter readings within their tenant', function () {
        $reading = MeterReading::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $reading->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $reading->tenant_id]);

        expect(checkPermission($admin, 'view', $reading))->toBeTrue()
            ->and(checkPermission($manager, 'view', $reading))->toBeTrue();
    });

    test('tenants can view meter readings for their properties', function () {
        $property = Property::factory()->create();
        $tenant = Tenant::factory()->create(['property_id' => $property->id]);
        $meter = Meter::factory()->create(['property_id' => $property->id]);
        $reading = MeterReading::factory()->create(['meter_id' => $meter->id, 'tenant_id' => $property->tenant_id]);
        
        $tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'email' => $tenant->email,
            'tenant_id' => $property->tenant_id,
        ]);

        expect(checkPermission($tenantUser, 'view', $reading))->toBeTrue();
    });

    test('admins and managers can create meter readings within their tenant', function () {
        $tenantId = 1;
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $tenantId]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $tenantId]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => $tenantId]);

        expect(checkPermission($admin, 'create', MeterReading::class))->toBeTrue()
            ->and(checkPermission($manager, 'create', MeterReading::class))->toBeTrue()
            ->and(checkPermission($tenant, 'create', MeterReading::class))->toBeFalse();
    });

    test('admins and managers can update meter readings within their tenant', function () {
        $reading = MeterReading::factory()->create(['validation_status' => ValidationStatus::PENDING]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $reading->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $reading->tenant_id]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => $reading->tenant_id]);

        // MeterReadingPolicy.update returns Response object, so we check allowed()
        auth()->login($admin);
        $adminResult = $admin->can('update', $reading);
        auth()->logout();
        
        auth()->login($manager);
        $managerResult = $manager->can('update', $reading);
        auth()->logout();
        
        auth()->login($tenant);
        $tenantResult = $tenant->can('update', $reading);
        auth()->logout();

        expect($adminResult)->toBeTrue()
            ->and($managerResult)->toBeTrue()
            ->and($tenantResult)->toBeFalse();
    });

    test('only admins can delete meter readings within their tenant', function () {
        $reading = MeterReading::factory()->create(['validation_status' => ValidationStatus::PENDING]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $reading->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $reading->tenant_id]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => $reading->tenant_id]);

        auth()->login($admin);
        $adminResult = $admin->can('delete', $reading);
        auth()->logout();
        
        auth()->login($manager);
        $managerResult = $manager->can('delete', $reading);
        auth()->logout();
        
        auth()->login($tenant);
        $tenantResult = $tenant->can('delete', $reading);
        auth()->logout();

        expect($adminResult)->toBeTrue()
            ->and($managerResult)->toBeFalse()
            ->and($tenantResult)->toBeFalse();
    });
});

describe('UserPolicy', function () {
    test('only admins can view any users', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('viewAny', User::class))->toBeTrue()
            ->and($manager->can('viewAny', User::class))->toBeFalse()
            ->and($tenant->can('viewAny', User::class))->toBeFalse();
    });

    test('admins can view any user', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $otherUser = User::factory()->create(['role' => UserRole::MANAGER]);

        expect($admin->can('view', $otherUser))->toBeTrue();
    });

    test('users can view their own profile', function () {
        $user = User::factory()->create(['role' => UserRole::MANAGER]);

        expect($user->can('view', $user))->toBeTrue();
    });

    test('users cannot view other users profiles', function () {
        $user1 = User::factory()->create(['role' => UserRole::MANAGER]);
        $user2 = User::factory()->create(['role' => UserRole::MANAGER]);

        expect($user1->can('view', $user2))->toBeFalse();
    });

    test('admins and managers can create users', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        // UserPolicy allows managers to create users (permissive workflow)
        expect($admin->can('create', User::class))->toBeTrue()
            ->and($manager->can('create', User::class))->toBeTrue()
            ->and($tenant->can('create', User::class))->toBeFalse();
    });

    test('admins can update any user', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $otherUser = User::factory()->create(['role' => UserRole::MANAGER]);

        expect($admin->can('update', $otherUser))->toBeTrue();
    });

    test('users can update their own profile', function () {
        $user = User::factory()->create(['role' => UserRole::MANAGER]);

        expect($user->can('update', $user))->toBeTrue();
    });

    test('admins and managers can delete users within their tenant', function () {
        $tenantId = 1;
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $tenantId]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $tenantId]);
        $otherUser = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => $tenantId]);

        // UserPolicy allows managers to delete users (permissive workflow)
        expect($admin->can('delete', $otherUser))->toBeTrue()
            ->and($manager->can('delete', $otherUser))->toBeTrue();
    });

    test('admins cannot delete themselves', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        expect($admin->can('delete', $admin))->toBeFalse();
    });
});

describe('PropertyPolicy', function () {
    test('admins and managers can view any properties within their tenant', function () {
        $tenantId = 1;
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $tenantId]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $tenantId]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => $tenantId]);

        expect(checkPermission($admin, 'viewAny', Property::class))->toBeTrue()
            ->and(checkPermission($manager, 'viewAny', Property::class))->toBeTrue()
            ->and(checkPermission($tenant, 'viewAny', Property::class))->toBeFalse();
    });

    test('admins and managers can view properties within their tenant', function () {
        $property = Property::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $property->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $property->tenant_id]);

        expect(checkPermission($admin, 'view', $property))->toBeTrue()
            ->and(checkPermission($manager, 'view', $property))->toBeTrue();
    });

    test('tenants can view their own property', function () {
        $property = Property::factory()->create();
        $tenant = Tenant::factory()->create(['property_id' => $property->id]);
        $tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'email' => $tenant->email,
            'tenant_id' => $property->tenant_id,
        ]);

        expect(checkPermission($tenantUser, 'view', $property))->toBeTrue();
    });

    test('admins and managers can create properties within their tenant', function () {
        $tenantId = 1;
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $tenantId]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $tenantId]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => $tenantId]);

        expect(checkPermission($admin, 'create', Property::class))->toBeTrue()
            ->and(checkPermission($manager, 'create', Property::class))->toBeTrue()
            ->and(checkPermission($tenant, 'create', Property::class))->toBeFalse();
    });

    test('admins and managers can update properties within their tenant', function () {
        $property = Property::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $property->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $property->tenant_id]);

        expect(checkPermission($admin, 'update', $property))->toBeTrue()
            ->and(checkPermission($manager, 'update', $property))->toBeTrue();
    });

    test('admins and managers can delete properties within their tenant', function () {
        $property = Property::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $property->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $property->tenant_id]);

        expect(checkPermission($admin, 'delete', $property))->toBeTrue()
            ->and(checkPermission($manager, 'delete', $property))->toBeTrue();
    });
});

describe('BuildingPolicy', function () {
    test('admins and managers can view any buildings', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('viewAny', Building::class))->toBeTrue()
            ->and($manager->can('viewAny', Building::class))->toBeTrue()
            ->and($tenant->can('viewAny', Building::class))->toBeFalse();
    });

    test('admins and managers can view buildings within their tenant', function () {
        $building = Building::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $building->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $building->tenant_id]);

        expect($admin->can('view', $building))->toBeTrue()
            ->and($manager->can('view', $building))->toBeTrue();
    });

    test('admins and managers can create buildings', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('create', Building::class))->toBeTrue()
            ->and($manager->can('create', Building::class))->toBeTrue()
            ->and($tenant->can('create', Building::class))->toBeFalse();
    });

    test('admins and managers can update buildings within their tenant', function () {
        $building = Building::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $building->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $building->tenant_id]);

        expect($admin->can('update', $building))->toBeTrue()
            ->and($manager->can('update', $building))->toBeTrue();
    });

    test('admins and managers can delete buildings within their tenant', function () {
        $building = Building::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $building->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $building->tenant_id]);

        expect($admin->can('delete', $building))->toBeTrue()
            ->and($manager->can('delete', $building))->toBeTrue();
    });
});

describe('MeterPolicy', function () {
    test('admins and managers can view any meters', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('viewAny', Meter::class))->toBeTrue()
            ->and($manager->can('viewAny', Meter::class))->toBeTrue()
            ->and($tenant->can('viewAny', Meter::class))->toBeFalse();
    });

    test('admins and managers can view meters within their tenant', function () {
        $meter = Meter::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $meter->property->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $meter->property->tenant_id]);

        expect($admin->can('view', $meter))->toBeTrue()
            ->and($manager->can('view', $meter))->toBeTrue();
    });

    test('tenants can view meters for their properties', function () {
        $property = Property::factory()->create();
        $tenant = Tenant::factory()->create(['property_id' => $property->id]);
        $meter = Meter::factory()->create(['property_id' => $property->id]);
        
        $tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'email' => $tenant->email,
            'tenant_id' => $property->tenant_id,
        ]);

        expect($tenantUser->can('view', $meter))->toBeTrue();
    });

    test('admins and managers can create meters', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('create', Meter::class))->toBeTrue()
            ->and($manager->can('create', Meter::class))->toBeTrue()
            ->and($tenant->can('create', Meter::class))->toBeFalse();
    });

    test('admins and managers can update meters within their tenant', function () {
        $meter = Meter::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $meter->property->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $meter->property->tenant_id]);

        expect($admin->can('update', $meter))->toBeTrue()
            ->and($manager->can('update', $meter))->toBeTrue();
    });

    test('admins and managers can delete meters within their tenant', function () {
        $meter = Meter::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $meter->property->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $meter->property->tenant_id]);

        expect($admin->can('delete', $meter))->toBeTrue()
            ->and($manager->can('delete', $meter))->toBeTrue();
    });
});

describe('ProviderPolicy', function () {
    test('only admins and superadmins can view any providers', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($superadmin->can('viewAny', Provider::class))->toBeTrue()
            ->and($admin->can('viewAny', Provider::class))->toBeTrue()
            ->and($manager->can('viewAny', Provider::class))->toBeFalse()
            ->and($tenant->can('viewAny', Provider::class))->toBeFalse();
    });

    test('only admins and superadmins can view individual providers', function () {
        $provider = Provider::factory()->create();
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($superadmin->can('view', $provider))->toBeTrue()
            ->and($admin->can('view', $provider))->toBeTrue()
            ->and($manager->can('view', $provider))->toBeFalse()
            ->and($tenant->can('view', $provider))->toBeFalse();
    });

    test('only admins and superadmins can create providers', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($superadmin->can('create', Provider::class))->toBeTrue()
            ->and($admin->can('create', Provider::class))->toBeTrue()
            ->and($manager->can('create', Provider::class))->toBeFalse()
            ->and($tenant->can('create', Provider::class))->toBeFalse();
    });

    test('only admins and superadmins can update providers', function () {
        $provider = Provider::factory()->create();
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($superadmin->can('update', $provider))->toBeTrue()
            ->and($admin->can('update', $provider))->toBeTrue()
            ->and($manager->can('update', $provider))->toBeFalse()
            ->and($tenant->can('update', $provider))->toBeFalse();
    });

    test('only admins and superadmins can delete providers', function () {
        $provider = Provider::factory()->create();
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($superadmin->can('delete', $provider))->toBeTrue()
            ->and($admin->can('delete', $provider))->toBeTrue()
            ->and($manager->can('delete', $provider))->toBeFalse()
            ->and($tenant->can('delete', $provider))->toBeFalse();
    });
});
