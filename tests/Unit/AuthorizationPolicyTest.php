<?php

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
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

    test('admins and managers can view all invoices', function () {
        $invoice = Invoice::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $invoice->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $invoice->tenant_id]);

        expect($admin->can('view', $invoice))->toBeTrue()
            ->and($manager->can('view', $invoice))->toBeTrue();
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

        expect($tenantUser->can('view', $invoice))->toBeTrue();
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

        expect($tenantUser2->can('view', $invoice1))->toBeFalse();
    });

    test('admins and managers can create invoices', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('create', Invoice::class))->toBeTrue()
            ->and($manager->can('create', Invoice::class))->toBeTrue()
            ->and($tenant->can('create', Invoice::class))->toBeFalse();
    });

    test('admins and managers can update draft invoices', function () {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $invoice->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $invoice->tenant_id]);

        expect($admin->can('update', $invoice))->toBeTrue()
            ->and($manager->can('update', $invoice))->toBeTrue();
    });

    test('finalized invoices cannot be updated', function () {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::FINALIZED]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $invoice->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $invoice->tenant_id]);

        expect($admin->can('update', $invoice))->toBeFalse()
            ->and($manager->can('update', $invoice))->toBeFalse();
    });

    test('admins and managers can finalize draft invoices', function () {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $invoice->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $invoice->tenant_id]);

        expect($admin->can('finalize', $invoice))->toBeTrue()
            ->and($manager->can('finalize', $invoice))->toBeTrue();
    });

    test('finalized invoices cannot be finalized again', function () {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::FINALIZED]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $invoice->tenant_id]);

        expect($admin->can('finalize', $invoice))->toBeFalse();
    });

    test('only admins can delete draft invoices', function () {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $invoice->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $invoice->tenant_id]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => $invoice->tenant_id]);

        expect($admin->can('delete', $invoice))->toBeTrue()
            ->and($manager->can('delete', $invoice))->toBeFalse()
            ->and($tenant->can('delete', $invoice))->toBeFalse();
    });

    test('finalized invoices cannot be deleted', function () {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::FINALIZED]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $invoice->tenant_id]);

        expect($admin->can('delete', $invoice))->toBeFalse();
    });
});

describe('MeterReadingPolicy', function () {
    test('all users can view any meter readings', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('viewAny', MeterReading::class))->toBeTrue()
            ->and($manager->can('viewAny', MeterReading::class))->toBeTrue()
            ->and($tenant->can('viewAny', MeterReading::class))->toBeTrue();
    });

    test('admins and managers can view all meter readings', function () {
        $reading = MeterReading::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $reading->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $reading->tenant_id]);

        expect($admin->can('view', $reading))->toBeTrue()
            ->and($manager->can('view', $reading))->toBeTrue();
    });

    test('tenants can view meter readings for their properties', function () {
        $property = Property::factory()->create();
        $tenant = Tenant::factory()->create(['property_id' => $property->id]);
        $reading = MeterReading::factory()->create(['tenant_id' => $property->tenant_id]);
        
        // Associate the meter with the property
        $reading->meter->update(['property_id' => $property->id]);
        
        $tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'email' => $tenant->email,
            'tenant_id' => $property->tenant_id,
        ]);

        expect($tenantUser->can('view', $reading))->toBeTrue();
    });

    test('admins and managers can create meter readings', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('create', MeterReading::class))->toBeTrue()
            ->and($manager->can('create', MeterReading::class))->toBeTrue()
            ->and($tenant->can('create', MeterReading::class))->toBeFalse();
    });

    test('admins and managers can update meter readings', function () {
        $reading = MeterReading::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $reading->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $reading->tenant_id]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => $reading->tenant_id]);

        expect($admin->can('update', $reading))->toBeTrue()
            ->and($manager->can('update', $reading))->toBeTrue()
            ->and($tenant->can('update', $reading))->toBeFalse();
    });

    test('only admins can delete meter readings', function () {
        $reading = MeterReading::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $reading->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $reading->tenant_id]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => $reading->tenant_id]);

        expect($admin->can('delete', $reading))->toBeTrue()
            ->and($manager->can('delete', $reading))->toBeFalse()
            ->and($tenant->can('delete', $reading))->toBeFalse();
    });
});

describe('UserPolicy', function () {
    test('only admins can view any users', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('viewAny', User::class))->toBeTrue()
            ->and($manager->can('viewAny', User::class))->toBeTrue()
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

        expect($admin->can('delete', $otherUser))->toBeTrue()
            ->and($manager->can('delete', $otherUser))->toBeTrue();
    });

    test('admins cannot delete themselves', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        expect($admin->can('delete', $admin))->toBeFalse();
    });
});

describe('PropertyPolicy', function () {
    test('admins and managers can view any properties', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('viewAny', Property::class))->toBeTrue()
            ->and($manager->can('viewAny', Property::class))->toBeTrue()
            ->and($tenant->can('viewAny', Property::class))->toBeFalse();
    });

    test('admins and managers can view properties within their tenant', function () {
        $property = Property::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $property->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $property->tenant_id]);

        expect($admin->can('view', $property))->toBeTrue()
            ->and($manager->can('view', $property))->toBeTrue();
    });

    test('tenants can view their own property', function () {
        $property = Property::factory()->create();
        $tenant = Tenant::factory()->create(['property_id' => $property->id]);
        $tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'email' => $tenant->email,
            'tenant_id' => $property->tenant_id,
        ]);

        expect($tenantUser->can('view', $property))->toBeTrue();
    });

    test('admins and managers can create properties', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('create', Property::class))->toBeTrue()
            ->and($manager->can('create', Property::class))->toBeTrue()
            ->and($tenant->can('create', Property::class))->toBeFalse();
    });

    test('admins and managers can update properties within their tenant', function () {
        $property = Property::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $property->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $property->tenant_id]);

        expect($admin->can('update', $property))->toBeTrue()
            ->and($manager->can('update', $property))->toBeTrue();
    });

    test('admins and managers can delete properties', function () {
        $property = Property::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $property->tenant_id]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $property->tenant_id]);

        expect($admin->can('delete', $property))->toBeTrue()
            ->and($manager->can('delete', $property))->toBeTrue();
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
    test('all users can view any meters', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('viewAny', Meter::class))->toBeTrue()
            ->and($manager->can('viewAny', Meter::class))->toBeTrue()
            ->and($tenant->can('viewAny', Meter::class))->toBeTrue();
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
    test('all users can view any providers', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('viewAny', Provider::class))->toBeTrue()
            ->and($manager->can('viewAny', Provider::class))->toBeTrue()
            ->and($tenant->can('viewAny', Provider::class))->toBeTrue();
    });

    test('all users can view individual providers', function () {
        $provider = Provider::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('view', $provider))->toBeTrue()
            ->and($manager->can('view', $provider))->toBeTrue()
            ->and($tenant->can('view', $provider))->toBeTrue();
    });

    test('only admins can create providers', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('create', Provider::class))->toBeTrue()
            ->and($manager->can('create', Provider::class))->toBeFalse()
            ->and($tenant->can('create', Provider::class))->toBeFalse();
    });

    test('only admins can update providers', function () {
        $provider = Provider::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('update', $provider))->toBeTrue()
            ->and($manager->can('update', $provider))->toBeFalse()
            ->and($tenant->can('update', $provider))->toBeFalse();
    });

    test('only admins can delete providers', function () {
        $provider = Provider::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        expect($admin->can('delete', $provider))->toBeTrue()
            ->and($manager->can('delete', $provider))->toBeFalse()
            ->and($tenant->can('delete', $provider))->toBeFalse();
    });
});
