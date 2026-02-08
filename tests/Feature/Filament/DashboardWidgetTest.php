<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget\Stat;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->organizationTenant = \App\Models\Tenant::factory()->create();
});

it('renders dashboard stats widget for admin users', function () {
    $admin = User::factory()->create([
        'tenant_id' => $this->organizationTenant->id,
        'role' => UserRole::ADMIN,
        'is_active' => true,
    ]);

    Property::factory()->count(5)->create(['tenant_id' => $this->organizationTenant->id]);
    Building::factory()->count(3)->create(['tenant_id' => $this->organizationTenant->id]);
    User::factory()->count(10)->create([
        'tenant_id' => $this->organizationTenant->id,
        'role' => UserRole::TENANT,
        'is_active' => true,
    ]);

    actingAs($admin);

    $widget = new \App\Filament\Pages\DashboardStatsWidget();
    $stats = $widget->getStats();
    
    expect($stats)->toBeArray()
        ->and($stats)->toHaveCount(6)
        ->and($stats[0])->toBeInstanceOf(Stat::class);
});

it('shows correct property count for admin', function () {
    $admin = User::factory()->create([
        'tenant_id' => $this->organizationTenant->id,
        'role' => UserRole::ADMIN,
        'is_active' => true,
    ]);

    actingAs($admin);

    $initialCount = Property::where('tenant_id', $this->organizationTenant->id)->count();
    Property::factory()->count(7)->create(['tenant_id' => $this->organizationTenant->id]);

    $widget = new \App\Filament\Pages\DashboardStatsWidget();
    $stats = $widget->getStats();

    expect($stats[0]->getValue())->toBe($initialCount + 7);
});

it('shows correct building count for admin', function () {
    $admin = User::factory()->create([
        'tenant_id' => $this->organizationTenant->id,
        'role' => UserRole::ADMIN,
        'is_active' => true,
    ]);

    actingAs($admin);

    $initialCount = Building::where('tenant_id', $this->organizationTenant->id)->count();
    Building::factory()->count(4)->create(['tenant_id' => $this->organizationTenant->id]);

    $widget = new \App\Filament\Pages\DashboardStatsWidget();
    $stats = $widget->getStats();

    expect($stats[1]->getValue())->toBe($initialCount + 4);
});

it('shows correct active tenant count for admin', function () {
    $admin = User::factory()->create([
        'tenant_id' => $this->organizationTenant->id,
        'role' => UserRole::ADMIN,
        'is_active' => true,
    ]);

    User::factory()->count(8)->create([
        'tenant_id' => $this->organizationTenant->id,
        'role' => UserRole::TENANT,
        'is_active' => true,
    ]);

    User::factory()->count(2)->create([
        'tenant_id' => $this->organizationTenant->id,
        'role' => UserRole::TENANT,
        'is_active' => false,
    ]);

    actingAs($admin);

    $widget = new \App\Filament\Pages\DashboardStatsWidget();
    $stats = $widget->getStats();

    expect($stats[2]->getValue())->toBe(8);
});

it('shows correct draft invoice count for admin', function () {
    $admin = User::factory()->create([
        'tenant_id' => $this->organizationTenant->id,
        'role' => UserRole::ADMIN,
        'is_active' => true,
    ]);

    $property = Property::factory()->create(['tenant_id' => $this->organizationTenant->id]);
    $renter = Tenant::factory()->create(['property_id' => $property->id]);

    Invoice::factory()->count(3)->create([
        'tenant_id' => $this->organizationTenant->id,
        'tenant_renter_id' => $renter->id,
        'finalized_at' => null,
    ]);

    Invoice::factory()->count(2)->create([
        'tenant_id' => $this->organizationTenant->id,
        'tenant_renter_id' => $renter->id,
        'finalized_at' => now(),
    ]);

    actingAs($admin);

    $widget = new \App\Filament\Pages\DashboardStatsWidget();
    $stats = $widget->getStats();

    expect($stats[3]->getValue())->toBe(3);
});

it('renders dashboard stats widget for manager users', function () {
    $manager = User::factory()->create([
        'tenant_id' => $this->organizationTenant->id,
        'role' => UserRole::MANAGER,
        'is_active' => true,
    ]);

    Property::factory()->count(3)->create(['tenant_id' => $this->organizationTenant->id]);
    Building::factory()->count(2)->create(['tenant_id' => $this->organizationTenant->id]);

    actingAs($manager);

    $widget = new \App\Filament\Pages\DashboardStatsWidget();
    $stats = $widget->getStats();
    
    expect($stats)->toBeArray()
        ->and($stats)->toHaveCount(4);
});

it('shows correct property count for manager', function () {
    $manager = User::factory()->create([
        'tenant_id' => $this->organizationTenant->id,
        'role' => UserRole::MANAGER,
        'is_active' => true,
    ]);

    actingAs($manager);

    $initialCount = Property::where('tenant_id', $this->organizationTenant->id)->count();
    Property::factory()->count(5)->create(['tenant_id' => $this->organizationTenant->id]);

    $widget = new \App\Filament\Pages\DashboardStatsWidget();
    $stats = $widget->getStats();

    expect($stats[0]->getValue())->toBe($initialCount + 5);
});

it('renders dashboard stats widget for tenant users', function () {
    $property = Property::factory()->create(['tenant_id' => $this->organizationTenant->id]);
    $renter = Tenant::factory()->create(['property_id' => $property->id]);
    
    $tenantUser = User::factory()->create([
        'tenant_id' => $this->organizationTenant->id,
        'property_id' => $property->id,
        'role' => UserRole::TENANT,
        'is_active' => true,
    ]);

    Invoice::factory()->count(2)->create([
        'tenant_id' => $this->organizationTenant->id,
        'tenant_renter_id' => $renter->id,
    ]);

    actingAs($tenantUser);

    $widget = new \App\Filament\Pages\DashboardStatsWidget();
    $stats = $widget->getStats();
    
    expect($stats)->toBeArray()
        ->and($stats)->toHaveCount(3);
});

it('shows correct property address for tenant', function () {
    $property = Property::factory()->create([
        'tenant_id' => $this->organizationTenant->id,
        'address' => '123 Test Street',
    ]);
    
    $tenantUser = User::factory()->create([
        'tenant_id' => $this->organizationTenant->id,
        'property_id' => $property->id,
        'role' => UserRole::TENANT,
        'is_active' => true,
    ]);

    actingAs($tenantUser);

    $widget = new \App\Filament\Pages\DashboardStatsWidget();
    $stats = $widget->getStats();

    expect($stats[0]->getValue())->toBe('123 Test Street');
});

it('shows correct invoice count for tenant', function () {
    $property = Property::factory()->create(['tenant_id' => $this->organizationTenant->id]);
    $renter = Tenant::factory()->create(['property_id' => $property->id]);
    
    $tenantUser = User::factory()->create([
        'tenant_id' => $this->organizationTenant->id,
        'property_id' => $property->id,
        'role' => UserRole::TENANT,
        'is_active' => true,
    ]);

    Invoice::factory()->count(4)->create([
        'tenant_id' => $this->organizationTenant->id,
        'tenant_renter_id' => $renter->id,
    ]);

    actingAs($tenantUser);

    $widget = new \App\Filament\Pages\DashboardStatsWidget();
    $stats = $widget->getStats();

    expect($stats[1]->getValue())->toBe(4);
});

it('shows correct unpaid invoice count for tenant', function () {
    $property = Property::factory()->create(['tenant_id' => $this->organizationTenant->id]);
    $renter = Tenant::factory()->create(['property_id' => $property->id]);
    
    $tenantUser = User::factory()->create([
        'tenant_id' => $this->organizationTenant->id,
        'property_id' => $property->id,
        'role' => UserRole::TENANT,
        'is_active' => true,
    ]);

    Invoice::factory()->count(3)->create([
        'tenant_id' => $this->organizationTenant->id,
        'tenant_renter_id' => $renter->id,
        'status' => \App\Enums\InvoiceStatus::FINALIZED,
        'finalized_at' => now(),
    ]);

    Invoice::factory()->count(2)->create([
        'tenant_id' => $this->organizationTenant->id,
        'tenant_renter_id' => $renter->id,
        'status' => \App\Enums\InvoiceStatus::PAID,
        'finalized_at' => now(),
    ]);

    actingAs($tenantUser);

    $widget = new \App\Filament\Pages\DashboardStatsWidget();
    $stats = $widget->getStats();

    expect($stats[2]->getValue())->toBe(3);
});

it('returns empty stats for unauthenticated users', function () {
    $widget = new \App\Filament\Pages\DashboardStatsWidget();
    $stats = $widget->getStats();

    expect($stats)->toBeArray()
        ->and($stats)->toBeEmpty();
});

it('isolates tenant data correctly', function () {
    $orgTenant1 = \App\Models\Tenant::factory()->create();
    $orgTenant2 = \App\Models\Tenant::factory()->create();

    $admin1 = User::factory()->create([
        'tenant_id' => $orgTenant1->id,
        'role' => UserRole::ADMIN,
    ]);

    Property::factory()->count(5)->create(['tenant_id' => $orgTenant1->id]);
    Property::factory()->count(10)->create(['tenant_id' => $orgTenant2->id]);

    actingAs($admin1);

    $widget = new \App\Filament\Pages\DashboardStatsWidget();
    $stats = $widget->getStats();

    expect($stats[0]->getValue())->toBe(5);
});

it('calculates revenue correctly for admin', function () {
    $admin = User::factory()->create([
        'tenant_id' => $this->organizationTenant->id,
        'role' => UserRole::ADMIN,
    ]);

    $property = Property::factory()->create(['tenant_id' => $this->organizationTenant->id]);
    $renter = Tenant::factory()->create(['property_id' => $property->id]);

    Invoice::factory()->create([
        'tenant_id' => $this->organizationTenant->id,
        'tenant_renter_id' => $renter->id,
        'finalized_at' => now(),
        'total_amount' => 10000, // €100.00
        'created_at' => now(),
    ]);

    Invoice::factory()->create([
        'tenant_id' => $this->organizationTenant->id,
        'tenant_renter_id' => $renter->id,
        'finalized_at' => now(),
        'total_amount' => 5000, // €50.00
        'created_at' => now(),
    ]);

    actingAs($admin);

    $widget = new \App\Filament\Pages\DashboardStatsWidget();
    $stats = $widget->getStats();

    expect($stats[5]->getValue())->toBe('€150.00');
});

it('handles tenant without property gracefully', function () {
    $tenantUser = User::factory()->create([
        'tenant_id' => $this->organizationTenant->id,
        'property_id' => null,
        'role' => UserRole::TENANT,
        'is_active' => true,
    ]);

    actingAs($tenantUser);

    $widget = new \App\Filament\Pages\DashboardStatsWidget();
    $stats = $widget->getStats();

    expect($stats)->toBeArray()
        ->and($stats)->toBeEmpty();
});
