<?php

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Lease;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\PropertyAssignment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('renders the login demo accounts table with curated accounts', function () {
    $organization = Organization::factory()->create([
        'name' => 'Tenanto Demo Organization',
        'slug' => 'tenanto-demo-organization',
    ]);

    User::factory()->superadmin()->create([
        'name' => 'System Superadmin',
        'email' => 'superadmin@example.com',
    ]);

    User::factory()->admin()->create([
        'name' => 'Demo Admin',
        'email' => 'admin@example.com',
        'organization_id' => $organization->id,
    ]);

    User::factory()->manager()->create([
        'name' => 'Demo Manager',
        'email' => 'manager@example.com',
        'organization_id' => $organization->id,
    ]);

    User::factory()->manager()->create([
        'name' => 'Demo Billing Manager',
        'email' => 'billing.manager@example.com',
        'organization_id' => $organization->id,
    ]);

    User::factory()->tenant()->create([
        'name' => 'Alina Petrauskienė',
        'email' => 'tenant.alina@example.com',
        'organization_id' => $organization->id,
    ]);

    User::factory()->tenant()->create([
        'name' => 'Marius Jonaitis',
        'email' => 'tenant.marius@example.com',
        'organization_id' => $organization->id,
    ]);

    User::factory()->admin()->create([
        'name' => 'Outside User',
        'email' => 'outside@example.com',
        'organization_id' => $organization->id,
    ]);

    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSeeText('Username')
        ->assertSeeText('Password')
        ->assertSeeText('Role')
        ->assertSeeText('superadmin@example.com')
        ->assertSeeText('admin@example.com')
        ->assertSeeText('manager@example.com')
        ->assertSeeText('billing.manager@example.com')
        ->assertSeeText('tenant.alina@example.com')
        ->assertSeeText('tenant.marius@example.com')
        ->assertSeeText('password')
        ->assertSeeText('Superadmin')
        ->assertSeeText('Admin')
        ->assertSeeText('Manager')
        ->assertSeeText('Tenant')
        ->assertDontSeeText('outside@example.com')
        ->assertSee('data-demo-account-trigger', false);
});

it('default database seeder creates demo accounts for every role', function () {
    $this->seed(DatabaseSeeder::class);

    $usersByEmail = User::query()
        ->select(['id', 'email', 'role', 'password', 'organization_id'])
        ->whereIn('email', [
            'superadmin@example.com',
            'admin@example.com',
            'manager@example.com',
            'billing.manager@example.com',
            'tenant.alina@example.com',
            'tenant.marius@example.com',
        ])
        ->get()
        ->keyBy('email');

    expect($usersByEmail)->toHaveCount(6)
        ->and($usersByEmail['superadmin@example.com']->role)->toBe(UserRole::SUPERADMIN)
        ->and($usersByEmail['admin@example.com']->role)->toBe(UserRole::ADMIN)
        ->and($usersByEmail['manager@example.com']->role)->toBe(UserRole::MANAGER)
        ->and($usersByEmail['billing.manager@example.com']->role)->toBe(UserRole::MANAGER)
        ->and($usersByEmail['tenant.alina@example.com']->role)->toBe(UserRole::TENANT)
        ->and($usersByEmail['tenant.marius@example.com']->role)->toBe(UserRole::TENANT)
        ->and($usersByEmail['superadmin@example.com']->organization_id)->toBeNull()
        ->and($usersByEmail['admin@example.com']->organization_id)->not->toBeNull()
        ->and(Hash::check('password', $usersByEmail['superadmin@example.com']->password))->toBeTrue()
        ->and(PropertyAssignment::query()->count())->toBeGreaterThanOrEqual(2);

    $demoTenantIds = [
        $usersByEmail['tenant.alina@example.com']->id,
        $usersByEmail['tenant.marius@example.com']->id,
    ];

    $demoTenantPropertyIds = PropertyAssignment::query()
        ->whereIn('tenant_user_id', $demoTenantIds)
        ->whereNull('unassigned_at')
        ->pluck('property_id')
        ->all();

    expect(Lease::query()->whereIn('tenant_user_id', $demoTenantIds)->count())->toBeGreaterThanOrEqual(2)
        ->and(Meter::query()->whereIn('property_id', $demoTenantPropertyIds)->count())->toBeGreaterThanOrEqual(4)
        ->and(MeterReading::query()->whereIn('property_id', $demoTenantPropertyIds)->count())->toBeGreaterThanOrEqual(24)
        ->and(Invoice::query()->whereIn('tenant_user_id', $demoTenantIds)->count())->toBeGreaterThanOrEqual(4)
        ->and(InvoiceItem::query()->whereHas('invoice', fn ($query) => $query->whereIn('tenant_user_id', $demoTenantIds))->count())->toBeGreaterThanOrEqual(12);

    $demoOrganization = Organization::query()
        ->with([
            'currentSubscription:id,organization_id,plan,status,is_trial,property_limit_snapshot,tenant_limit_snapshot,meter_limit_snapshot,invoice_limit_snapshot',
            'settings:id,organization_id,billing_contact_email,billing_contact_phone',
        ])
        ->where('slug', 'tenanto-demo-organization')
        ->firstOrFail();

    expect($demoOrganization->currentSubscription?->plan)->toBe(SubscriptionPlan::PROFESSIONAL)
        ->and($demoOrganization->currentSubscription?->status)->toBe(SubscriptionStatus::ACTIVE)
        ->and($demoOrganization->currentSubscription?->is_trial)->toBeFalse()
        ->and($demoOrganization->currentSubscription?->property_limit_snapshot)->toBe(SubscriptionPlan::PROFESSIONAL->limits()['properties'])
        ->and($demoOrganization->settings?->billing_contact_email)->toBe('billing@tenanto.test')
        ->and($demoOrganization->settings?->billing_contact_phone)->toBe('+37060000000');

    $managerPermissionService = app(ManagerPermissionService::class);

    expect($managerPermissionService->can($usersByEmail['manager@example.com'], $demoOrganization, 'buildings', 'create'))->toBeTrue()
        ->and($managerPermissionService->can($usersByEmail['manager@example.com'], $demoOrganization, 'invoices', 'create'))->toBeFalse()
        ->and($managerPermissionService->can($usersByEmail['billing.manager@example.com'], $demoOrganization, 'billing', 'create'))->toBeTrue()
        ->and($managerPermissionService->can($usersByEmail['billing.manager@example.com'], $demoOrganization, 'providers', 'edit'))->toBeTrue()
        ->and($managerPermissionService->can($usersByEmail['billing.manager@example.com'], $demoOrganization, 'buildings', 'create'))->toBeFalse();
});
