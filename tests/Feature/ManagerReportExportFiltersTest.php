<?php

declare(strict_types=1);

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('revenue export respects building and status filters', function () {
    $tenantId = 1;
    $manager = User::factory()->manager($tenantId)->create();

    $buildingA = Building::factory()->forTenantId($tenantId)->create();
    $buildingB = Building::factory()->forTenantId($tenantId)->create();

    $propertyA = Property::factory()->forTenantId($tenantId)->create([
        'building_id' => $buildingA->id,
    ]);
    $propertyB = Property::factory()->forTenantId($tenantId)->create([
        'building_id' => $buildingB->id,
    ]);

    $tenantA = Tenant::factory()->forProperty($propertyA)->create();
    $tenantB = Tenant::factory()->forProperty($propertyB)->create();

    $periodStart = now()->startOfMonth()->addDay();
    $periodEnd = now()->startOfMonth()->addDays(28);

    $invoiceMatch = Invoice::factory()->forTenantRenter($tenantA)->paid()->create([
        'billing_period_start' => $periodStart,
        'billing_period_end' => $periodEnd,
    ]);

    $invoiceOtherBuilding = Invoice::factory()->forTenantRenter($tenantB)->paid()->create([
        'billing_period_start' => $periodStart,
        'billing_period_end' => $periodEnd,
    ]);

    $invoiceOtherStatus = Invoice::factory()->forTenantRenter($tenantA)->finalized()->create([
        'billing_period_start' => $periodStart,
        'billing_period_end' => $periodEnd,
    ]);

    $response = $this->actingAs($manager)->get(route('manager.reports.revenue.export', [
        'start_date' => $periodStart->format('Y-m-d'),
        'end_date' => $periodEnd->format('Y-m-d'),
        'building_id' => $buildingA->id,
        'status' => 'paid',
    ]));

    $response->assertSuccessful();

    $csv = $response->getContent();
    $rows = array_map('str_getcsv', array_filter(explode("\n", trim($csv))));
    $invoiceIds = array_column(array_slice($rows, 1), 0);

    expect($invoiceIds)->toContain((string) $invoiceMatch->id);
    expect($invoiceIds)->not->toContain((string) $invoiceOtherBuilding->id);
    expect($invoiceIds)->not->toContain((string) $invoiceOtherStatus->id);
});

test('compliance export respects building filter', function () {
    $tenantId = 1;
    $manager = User::factory()->manager($tenantId)->create();

    $buildingA = Building::factory()->forTenantId($tenantId)->create();
    $buildingB = Building::factory()->forTenantId($tenantId)->create();

    $propertyA = Property::factory()->forTenantId($tenantId)->create([
        'building_id' => $buildingA->id,
    ]);
    $propertyB = Property::factory()->forTenantId($tenantId)->create([
        'building_id' => $buildingB->id,
    ]);

    $meterA = Meter::factory()->forProperty($propertyA)->create();
    $meterB = Meter::factory()->forProperty($propertyB)->create();

    $month = now()->format('Y-m');
    $readingDate = now()->startOfMonth()->addDays(3);

    MeterReading::factory()->forMeter($meterA)->create([
        'reading_date' => $readingDate,
    ]);
    MeterReading::factory()->forMeter($meterB)->create([
        'reading_date' => $readingDate,
    ]);

    $response = $this->actingAs($manager)->get(route('manager.reports.compliance.export', [
        'month' => $month,
        'building_id' => $buildingA->id,
    ]));

    $response->assertSuccessful();

    $csv = $response->getContent();

    expect($csv)->toContain($propertyA->address);
    expect($csv)->not->toContain($propertyB->address);
});
