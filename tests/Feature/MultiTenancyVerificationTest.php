<?php

use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('TenantScope is applied to Property model', function () {
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Address 1',
        'type' => 'apartment',
        'area_sqm' => 50.00,
    ]);

    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'address' => 'Address 2',
        'type' => 'apartment',
        'area_sqm' => 60.00,
    ]);

    session(['tenant_id' => 1]);
    expect(Property::count())->toBe(1);
    expect(Property::first()->id)->toBe($property1->id);

    session(['tenant_id' => 2]);
    expect(Property::count())->toBe(1);
    expect(Property::first()->id)->toBe($property2->id);
});

test('TenantScope is applied to Meter model', function () {
    $meter1 = Meter::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'serial_number' => 'M001',
        'type' => 'electricity',
        'installation_date' => now(),
        'supports_zones' => false,
    ]);

    $meter2 = Meter::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'serial_number' => 'M002',
        'type' => 'water_cold',
        'installation_date' => now(),
        'supports_zones' => false,
    ]);

    session(['tenant_id' => 1]);
    expect(Meter::count())->toBe(1);
    expect(Meter::first()->id)->toBe($meter1->id);

    session(['tenant_id' => 2]);
    expect(Meter::count())->toBe(1);
    expect(Meter::first()->id)->toBe($meter2->id);
});

test('TenantScope is applied to MeterReading model', function () {
    $reading1 = MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'meter_id' => 1,
        'reading_date' => now(),
        'value' => 100.00,
        'entered_by' => 1,
    ]);

    $reading2 = MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'meter_id' => 2,
        'reading_date' => now(),
        'value' => 200.00,
        'entered_by' => 1,
    ]);

    session(['tenant_id' => 1]);
    expect(MeterReading::count())->toBe(1);
    expect(MeterReading::first()->id)->toBe($reading1->id);

    session(['tenant_id' => 2]);
    expect(MeterReading::count())->toBe(1);
    expect(MeterReading::first()->id)->toBe($reading2->id);
});

test('TenantScope is applied to Invoice model', function () {
    $invoice1 = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'billing_period_start' => now()->subMonth(),
        'billing_period_end' => now(),
        'total_amount' => 100.00,
        'status' => 'draft',
    ]);

    $invoice2 = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'billing_period_start' => now()->subMonth(),
        'billing_period_end' => now(),
        'total_amount' => 200.00,
        'status' => 'draft',
    ]);

    session(['tenant_id' => 1]);
    expect(Invoice::count())->toBe(1);
    expect(Invoice::first()->id)->toBe($invoice1->id);

    session(['tenant_id' => 2]);
    expect(Invoice::count())->toBe(1);
    expect(Invoice::first()->id)->toBe($invoice2->id);
});

test('TenantScope is applied to Tenant model', function () {
    $tenant1 = Tenant::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'name' => 'Tenant 1',
        'email' => 'tenant1@example.com',
        'phone' => '123456789',
        'lease_start' => now(),
        'lease_end' => now()->addYear(),
    ]);

    $tenant2 = Tenant::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'name' => 'Tenant 2',
        'email' => 'tenant2@example.com',
        'phone' => '987654321',
        'lease_start' => now(),
        'lease_end' => now()->addYear(),
    ]);

    session(['tenant_id' => 1]);
    expect(Tenant::count())->toBe(1);
    expect(Tenant::first()->id)->toBe($tenant1->id);

    session(['tenant_id' => 2]);
    expect(Tenant::count())->toBe(1);
    expect(Tenant::first()->id)->toBe($tenant2->id);
});

test('authentication event sets tenant_id in session', function () {
    $user = User::factory()->create(['tenant_id' => 999]);

    auth()->login($user);

    expect(session('tenant_id'))->toBe(999);
});

test('cross-tenant access returns empty results', function () {
    Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Address 1',
        'type' => 'apartment',
        'area_sqm' => 50.00,
    ]);

    Property::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'address' => 'Address 2',
        'type' => 'apartment',
        'area_sqm' => 60.00,
    ]);

    // Set session to tenant 1
    session(['tenant_id' => 1]);

    // Should only see tenant 1's property
    expect(Property::count())->toBe(1);

    // Try to access tenant 2's property by ID
    $property2 = Property::withoutGlobalScopes()->where('tenant_id', 2)->first();
    $result = Property::find($property2->id);

    // Should return null because of tenant scope
    expect($result)->toBeNull();
});
