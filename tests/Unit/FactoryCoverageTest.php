<?php

use App\Enums\MeterType;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('building factory can target tenant', function () {
    $building = Building::factory()->forTenantId(5)->create();

    expect($building->tenant_id)->toBe(5);
});

test('property factory aligns tenant with building', function () {
    $property = Property::factory()->forTenantId(7)->create();

    expect($property->tenant_id)->toBe(7)
        ->and($property->building->tenant_id)->toBe(7);
});

test('tenant factory syncs tenant_id with property', function () {
    $property = Property::factory()->forTenantId(3)->create();

    $tenant = Tenant::factory()->forProperty($property)->create();

    expect($tenant->tenant_id)->toBe(3)
        ->and($tenant->property_id)->toBe($property->id);
});

test('tenant factory records pivot assignment', function () {
    $property = Property::factory()->forTenantId(9)->create();
    $leaseStart = Carbon::now()->subMonths(2);
    $leaseEnd = Carbon::now()->addMonths(6);

    $tenant = Tenant::factory()->forProperty($property)->create([
        'lease_start' => $leaseStart,
        'lease_end' => $leaseEnd,
    ]);

    $pivot = DB::table('property_tenant')
        ->where('tenant_id', $tenant->id)
        ->first();

    expect($pivot)->not->toBeNull()
        ->and($pivot->property_id)->toBe($property->id)
        ->and(Carbon::parse($pivot->assigned_at)->toDateString())->toBe($leaseStart->toDateString())
        ->and($pivot->vacated_at)->toBeNull();
});

test('meter factory respects property tenant', function () {
    $property = Property::factory()->forTenantId(4)->create();

    $meter = Meter::factory()->forProperty($property)->create();

    expect($meter->tenant_id)->toBe(4)
        ->and($meter->property_id)->toBe($property->id);
});

test('meter reading factory keeps tenant aligned', function () {
    $meter = Meter::factory()
        ->forTenantId(6)
        ->state(['type' => MeterType::WATER_COLD])
        ->create();

    $reading = MeterReading::factory()->forMeter($meter)->create();

    expect($meter->tenant_id)->toBe(6)
        ->and($meter->property->tenant_id)->toBe(6)
        ->and($reading->tenant_id)->toBe(6)
        ->and($reading->meter_id)->toBe($meter->id)
        ->and($reading->enteredBy->tenant_id)->toBe(6);
});

test('invoice factory syncs renter and tenant', function () {
    $propertyTenant = Tenant::factory()->forTenantId(8)->create();

    $invoice = Invoice::factory()->forTenantRenter($propertyTenant)->create();

    expect($propertyTenant->tenant_id)->toBe(8)
        ->and($invoice->tenant_id)->toBe(8)
        ->and($invoice->tenant_renter_id)->toBe($propertyTenant->id);
});

test('invoice factory paid state sets payment details', function () {
    $tenant = Tenant::factory()->forTenantId(11)->create();

    $invoice = Invoice::factory()
        ->forTenantRenter($tenant)
        ->paid()
        ->create();

    expect($invoice->status)->toBe(\App\Enums\InvoiceStatus::PAID)
        ->and($invoice->paid_at)->not->toBeNull()
        ->and($invoice->payment_reference)->not->toBeNull()
        ->and(trim($invoice->payment_reference))->not->toBe('')
        ->and((float) $invoice->paid_amount)->toBeGreaterThan(0);
});

test('invoice item factory keeps totals consistent', function () {
    $item = InvoiceItem::factory()->create([
        'quantity' => 10,
        'unit_price' => 2.5,
    ]);

    expect((float) $item->total)->toBe(25.0);
});
