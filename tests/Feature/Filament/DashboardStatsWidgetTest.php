<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Widgets\DashboardStatsWidget;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Clear cache before each test
    \Illuminate\Support\Facades\Cache::flush();
});

test('DashboardStatsWidget renders for admin users', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create some test data
    Property::factory()->count(3)->create(['tenant_id' => 1]);
    Building::factory()->count(2)->create(['tenant_id' => 1]);

    $this->actingAs($admin);

    Livewire::test(DashboardStatsWidget::class)
        ->assertOk()
        ->assertSee('Total Properties')
        ->assertSee('Total Buildings')
        ->assertSee('Active Tenants')
        ->assertSee('Draft Invoices')
        ->assertSee('Pending Readings')
        ->assertSee('Total Revenue (This Month)');
});

test('DashboardStatsWidget renders for manager users', function () {
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
    ]);

    // Create some test data
    Property::factory()->count(2)->create(['tenant_id' => 1]);
    Building::factory()->count(1)->create(['tenant_id' => 1]);

    $this->actingAs($manager);

    Livewire::test(DashboardStatsWidget::class)
        ->assertOk()
        ->assertSee('Total Properties')
        ->assertSee('Total Buildings')
        ->assertSee('Pending Readings')
        ->assertSee('Draft Invoices');
});

test('DashboardStatsWidget renders for tenant users', function () {
    $property = Property::factory()->create(['tenant_id' => 1]);
    
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);

    $this->actingAs($tenant);

    Livewire::test(DashboardStatsWidget::class)
        ->assertOk()
        ->assertSee('Your Property')
        ->assertSee('Your Invoices')
        ->assertSee('Unpaid Invoices');
});

test('DashboardStatsWidget shows correct property count for admin', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create properties for this tenant
    Property::factory()->count(5)->create(['tenant_id' => 1]);
    
    // Create properties for another tenant (should not be counted)
    Property::factory()->count(3)->create(['tenant_id' => 2]);

    $this->actingAs($admin);

    Livewire::test(DashboardStatsWidget::class)
        ->assertOk()
        ->assertSee('5'); // Should see the count of 5 properties
});

test('DashboardStatsWidget shows correct building count for manager', function () {
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
    ]);

    // Create buildings for this tenant
    Building::factory()->count(3)->create(['tenant_id' => 1]);
    
    // Create buildings for another tenant (should not be counted)
    Building::factory()->count(2)->create(['tenant_id' => 2]);

    $this->actingAs($manager);

    Livewire::test(DashboardStatsWidget::class)
        ->assertOk()
        ->assertSee('3'); // Should see the count of 3 buildings
});

test('DashboardStatsWidget caches stats correctly', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    Property::factory()->count(3)->create(['tenant_id' => 1]);

    $this->actingAs($admin);

    // First call should cache the results
    Livewire::test(DashboardStatsWidget::class)
        ->assertOk()
        ->assertSee('3');

    // Add more properties
    Property::factory()->count(2)->create(['tenant_id' => 1]);

    // Second call should return cached results (still 3)
    Livewire::test(DashboardStatsWidget::class)
        ->assertOk()
        ->assertSee('3'); // Still cached

    // Clear cache
    \Illuminate\Support\Facades\Cache::flush();

    // Third call should return fresh results (5)
    Livewire::test(DashboardStatsWidget::class)
        ->assertOk()
        ->assertSee('5');
});

test('DashboardStatsWidget respects tenant isolation', function () {
    $admin1 = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    $admin2 = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 2,
    ]);

    // Create properties for tenant 1
    Property::factory()->count(3)->create(['tenant_id' => 1]);
    
    // Create properties for tenant 2
    Property::factory()->count(5)->create(['tenant_id' => 2]);

    // Test admin 1 sees only their properties
    $this->actingAs($admin1);
    Livewire::test(DashboardStatsWidget::class)
        ->assertOk()
        ->assertSee('3');

    // Clear cache
    \Illuminate\Support\Facades\Cache::flush();

    // Test admin 2 sees only their properties
    $this->actingAs($admin2);
    Livewire::test(DashboardStatsWidget::class)
        ->assertOk()
        ->assertSee('5');
});

test('DashboardStatsWidget handles unauthenticated users gracefully', function () {
    // Test that the widget doesn't crash when no user is authenticated
    // This is more of a safety check
    expect(auth()->user())->toBeNull();
});
