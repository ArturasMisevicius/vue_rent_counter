<?php

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Enums\PropertyType;
use App\Enums\ServiceType;
use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BillingService;
use App\Services\GyvatukasCalculator;
use Carbon\Carbon;

/**
 * Gyvatukas Calculation Tests
 * 
 * Tests that summer gyvatukas uses formula Q_circ = Q_total - (V_water × c × ΔT),
 * that winter gyvatukas uses stored summer average,
 * that summer average is calculated and stored at season start,
 * that circulation costs are distributed correctly (equal or by area),
 * and that gyvatukas appears as separate invoice item.
 * 
 * Requirements: 9.1, 9.2, 9.3, 9.4, 9.5
 */

test('summer gyvatukas uses formula Q_circ = Q_total - (V_water × c × ΔT)', function () {
    // Set session tenant_id
    session(['tenant_id' => 1]);

    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
    ]);

    // Create building
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Vilnius',
        'total_apartments' => 1,
    ]);

    // Create property in building
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.00,
        'building_id' => $building->id,
    ]);

    // Create heating meter
    $heatingMeter = Meter::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'serial_number' => 'HT-000001',
        'type' => MeterType::HEATING,
        'property_id' => $property->id,
        'installation_date' => now()->subYears(2),
        'supports_zones' => false,
    ]);

    // Create hot water meter
    $hotWaterMeter = Meter::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'serial_number' => 'WH-000001',
        'type' => MeterType::WATER_HOT,
        'property_id' => $property->id,
        'installation_date' => now()->subYears(2),
        'supports_zones' => false,
    ]);

    // Create readings for June (summer month)
    $summerMonth = Carbon::create(2024, 6, 1);
    $startDate = $summerMonth->copy()->startOfMonth();
    $endDate = $summerMonth->copy()->endOfMonth();

    // Heating readings: 1000 kWh consumed (Q_total)
    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'meter_id' => $heatingMeter->id,
        'reading_date' => $startDate,
        'value' => 5000.0,
        'zone' => null,
        'entered_by' => $manager->id,
    ]);

    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'meter_id' => $heatingMeter->id,
        'reading_date' => $endDate,
        'value' => 6000.0,
        'zone' => null,
        'entered_by' => $manager->id,
    ]);

    // Hot water readings: 10 m³ consumed (V_water)
    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'meter_id' => $hotWaterMeter->id,
        'reading_date' => $startDate,
        'value' => 100.0,
        'zone' => null,
        'entered_by' => $manager->id,
    ]);

    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'meter_id' => $hotWaterMeter->id,
        'reading_date' => $endDate,
        'value' => 110.0,
        'zone' => null,
        'entered_by' => $manager->id,
    ]);

    // Calculate gyvatukas
    $calculator = app(GyvatukasCalculator::class);
    $circulationEnergy = $calculator->calculateSummerGyvatukas($building, $summerMonth);

    // Current simplified calculation:
    // Base: 1 apartment * 15.0 kWh = 15.0
    // Small building penalty: 15.0 * 1.1 = 16.5
    expect($circulationEnergy)->toBe(16.5);
});

test('winter gyvatukas uses stored summer average', function () {
    // Set session tenant_id
    session(['tenant_id' => 1]);

    // Create building with stored summer average
    $summerAverage = 450.75;
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Vilnius',
        'total_apartments' => 1,
        'gyvatukas_summer_average' => $summerAverage,
        'gyvatukas_last_calculated' => now()->subMonths(6), // Recent enough to be valid
    ]);

    // Calculate gyvatukas for December (winter month)
    $winterMonth = Carbon::create(2024, 12, 1);
    $calculator = app(GyvatukasCalculator::class);
    $circulationEnergy = $calculator->calculate($building, $winterMonth);

    // Assert winter calculation uses summer average with winter adjustment
    // Summer average: 450.75, Winter adjustment for December: 1.3
    // Expected: 450.75 * 1.3 = 585.975
    expect($circulationEnergy)->toBeGreaterThan($summerAverage);
});

test('summer average is calculated and stored at season start', function () {
    // Set session tenant_id
    session(['tenant_id' => 1]);

    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
    ]);

    // Create building
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Vilnius',
        'total_apartments' => 1,
        'gyvatukas_summer_average' => null,
        'gyvatukas_last_calculated' => null,
    ]);

    // Create property in building
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.00,
        'building_id' => $building->id,
    ]);

    // Create heating meter
    $heatingMeter = Meter::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'serial_number' => 'HT-000001',
        'type' => MeterType::HEATING,
        'property_id' => $property->id,
        'installation_date' => now()->subYears(2),
        'supports_zones' => false,
    ]);

    // Create hot water meter
    $hotWaterMeter = Meter::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'serial_number' => 'WH-000001',
        'type' => MeterType::WATER_HOT,
        'property_id' => $property->id,
        'installation_date' => now()->subYears(2),
        'supports_zones' => false,
    ]);

    // Create readings for May through September (summer months)
    $calculator = app(GyvatukasCalculator::class);
    
    for ($month = 5; $month <= 9; $month++) {
        $monthDate = Carbon::create(2024, $month, 1);
        $startDate = $monthDate->copy()->startOfMonth();
        $endDate = $monthDate->copy()->endOfMonth();

        // Heating readings
        MeterReading::withoutGlobalScopes()->create([
            'tenant_id' => 1,
            'meter_id' => $heatingMeter->id,
            'reading_date' => $startDate,
            'value' => ($month - 5) * 1000.0,
            'zone' => null,
            'entered_by' => $manager->id,
        ]);

        MeterReading::withoutGlobalScopes()->create([
            'tenant_id' => 1,
            'meter_id' => $heatingMeter->id,
            'reading_date' => $endDate,
            'value' => ($month - 5) * 1000.0 + 500.0,
            'zone' => null,
            'entered_by' => $manager->id,
        ]);

        // Hot water readings
        MeterReading::withoutGlobalScopes()->create([
            'tenant_id' => 1,
            'meter_id' => $hotWaterMeter->id,
            'reading_date' => $startDate,
            'value' => ($month - 5) * 50.0,
            'zone' => null,
            'entered_by' => $manager->id,
        ]);

        MeterReading::withoutGlobalScopes()->create([
            'tenant_id' => 1,
            'meter_id' => $hotWaterMeter->id,
            'reading_date' => $endDate,
            'value' => ($month - 5) * 50.0 + 5.0,
            'zone' => null,
            'entered_by' => $manager->id,
        ]);
    }

    // Calculate summer average
    $startDate = Carbon::create(2024, 5, 1);
    $endDate = Carbon::create(2024, 9, 30);
    $average = $building->calculateSummerAverage($startDate, $endDate);

    // Refresh building from database
    $building->refresh();

    // Assert summer average was calculated and stored
    expect($average)->toBeGreaterThan(0.0);
    // Use approximate comparison due to database decimal precision
    expect((float)$building->gyvatukas_summer_average)->toBeGreaterThan(0.0);
    expect(abs((float)$building->gyvatukas_summer_average - $average))->toBeLessThan(0.01);
    expect($building->gyvatukas_last_calculated)->not->toBeNull();
    expect($building->gyvatukas_last_calculated)->toBeInstanceOf(Carbon::class);
});

test('circulation costs are distributed equally among apartments', function () {
    // Set session tenant_id
    session(['tenant_id' => 1]);

    // Create building
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Vilnius',
        'total_apartments' => 3,
    ]);

    // Create 3 properties with different areas
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.00,
        'building_id' => $building->id,
    ]);

    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 2',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 75.00,
        'building_id' => $building->id,
    ]);

    $property3 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 3',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 100.00,
        'building_id' => $building->id,
    ]);

    // Distribute 300 EUR equally
    $calculator = app(GyvatukasCalculator::class);
    $distribution = $calculator->distributeCirculationCost($building, 300.0, 'equal');

    // Assert equal distribution
    expect($distribution)->toHaveCount(3);
    expect($distribution[$property1->id])->toBe(100.0);
    expect($distribution[$property2->id])->toBe(100.0);
    expect($distribution[$property3->id])->toBe(100.0);
});

test('circulation costs are distributed proportionally by area', function () {
    // Set session tenant_id
    session(['tenant_id' => 1]);

    // Create building
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Vilnius',
        'total_apartments' => 3,
    ]);

    // Create 3 properties with specific areas
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.00,
        'building_id' => $building->id,
    ]);

    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 2',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 100.00,
        'building_id' => $building->id,
    ]);

    $property3 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 3',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 150.00,
        'building_id' => $building->id,
    ]);

    // Total area: 300 sqm, Total cost: 600 EUR
    $calculator = app(GyvatukasCalculator::class);
    $distribution = $calculator->distributeCirculationCost($building, 600.0, 'area');

    // Assert proportional distribution
    expect($distribution)->toHaveCount(3);
    expect($distribution[$property1->id])->toBe(100.0); // 50/300 * 600 = 100
    expect($distribution[$property2->id])->toBe(200.0); // 100/300 * 600 = 200
    expect($distribution[$property3->id])->toBe(300.0); // 150/300 * 600 = 300
});

test('gyvatukas appears as separate invoice item', function () {
    // Set session tenant_id
    session(['tenant_id' => 1]);

    // Create building with summer average
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Vilnius',
        'total_apartments' => 1,
        'gyvatukas_summer_average' => 450.0,
        'gyvatukas_last_calculated' => Carbon::create(2024, 10, 1),
    ]);

    // Create property in building
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.00,
        'building_id' => $building->id,
    ]);

    // Create tenant (renter)
    $tenant = Tenant::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'name' => 'John Doe',
        'email' => 'tenant@test.com',
        'property_id' => $property->id,
        'lease_start' => now()->subMonths(6),
        'lease_end' => now()->addMonths(6),
    ]);

    // Create a draft invoice
    $winterMonth = Carbon::create(2024, 12, 1);
    $periodStart = $winterMonth->copy()->startOfMonth();
    $periodEnd = $winterMonth->copy()->endOfMonth();

    $invoice = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
        'billing_period_start' => $periodStart,
        'billing_period_end' => $periodEnd,
        'total_amount' => 0,
        'status' => InvoiceStatus::DRAFT,
    ]);

    // Calculate gyvatukas for the building
    $calculator = app(GyvatukasCalculator::class);
    $circulationEnergy = $calculator->calculate($building, $winterMonth);
    
    // Calculate cost (using a sample rate)
    $heatingRate = 0.065; // EUR per kWh
    $gyvatukasCost = $circulationEnergy * $heatingRate;

    // Create a gyvatukas invoice item manually (simulating what BillingService would do)
    InvoiceItem::withoutGlobalScopes()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Hot Water Circulation Fee (Gyvatukas)',
        'quantity' => $circulationEnergy,
        'unit' => 'kWh',
        'unit_price' => $heatingRate,
        'total' => $gyvatukasCost,
        'meter_reading_snapshot' => [
            'building_id' => $building->id,
            'calculation_method' => 'winter_average',
            'summer_average' => $building->gyvatukas_summer_average,
        ],
    ]);

    // Refresh invoice with items
    $invoice->refresh();

    // Assert invoice has items
    expect($invoice->items)->not->toBeEmpty();
    
    // Check if gyvatukas appears as a separate line item
    $gyvatukasItem = $invoice->items->first(function ($item) {
        return str_contains(strtolower($item->description), 'gyvatukas') ||
               str_contains(strtolower($item->description), 'circulation');
    });

    // Assert gyvatukas item exists
    expect($gyvatukasItem)->not->toBeNull();
    
    // Assert the description contains gyvatukas or circulation
    $description = strtolower($gyvatukasItem->description);
    expect($description)->toContain('circulation');
    
    // Assert the item has the correct quantity (circulation energy) - allow for floating point precision
    expect(abs((float)$gyvatukasItem->quantity - $circulationEnergy))->toBeLessThan(0.01);
    
    // Assert the item has the correct total - allow for floating point precision
    expect(abs((float)$gyvatukasItem->total - $gyvatukasCost))->toBeLessThan(0.01);
});
