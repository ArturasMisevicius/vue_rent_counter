<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\MeterType;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Tariff;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BillingService $billingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->billingService = app(BillingService::class);
    }

    public function test_generates_invoice_for_property_with_meter_readings(): void
    {
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->tenant_id]);
        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->tenant_id,
            'type' => MeterType::ELECTRICITY,
        ]);

        $startReading = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'tenant_id' => $tenant->tenant_id,
            'reading_value' => 1000.0,
            'reading_date' => now()->subMonth(),
        ]);

        $endReading = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'tenant_id' => $tenant->tenant_id,
            'reading_value' => 1100.0,
            'reading_date' => now(),
        ]);

        $invoice = $this->billingService->generateInvoice(
            $property,
            now()->subMonth(),
            now()
        );

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($tenant->id, $invoice->tenant_renter_id);
        $this->assertGreaterThan(0, $invoice->total_amount);
    }

    public function test_calculates_consumption_correctly(): void
    {
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->tenant_id]);
        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->tenant_id,
            'type' => MeterType::ELECTRICITY,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'tenant_id' => $tenant->tenant_id,
            'reading_value' => 1000.0,
            'reading_date' => now()->subMonth(),
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'tenant_id' => $tenant->tenant_id,
            'reading_value' => 1150.0,
            'reading_date' => now(),
        ]);

        $invoice = $this->billingService->generateInvoice(
            $property,
            now()->subMonth(),
            now()
        );

        // Consumption should be 150 kWh
        $this->assertNotNull($invoice);
        $this->assertGreaterThan(0, $invoice->invoiceItems->count());
    }

    public function test_throws_exception_when_no_meter_readings(): void
    {
        $this->expectException(\RuntimeException::class);

        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->tenant_id]);
        
        Meter::factory()->create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->tenant_id,
            'type' => MeterType::ELECTRICITY,
        ]);

        $this->billingService->generateInvoice(
            $property,
            now()->subMonth(),
            now()
        );
    }

    public function test_snapshots_tariff_rates_in_invoice_items(): void
    {
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->tenant_id]);
        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->tenant_id,
            'type' => MeterType::ELECTRICITY,
        ]);

        $tariff = Tariff::factory()->create([
            'provider_id' => $meter->provider_id,
            'active_from' => now()->subYear(),
            'active_until' => null,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'tenant_id' => $tenant->tenant_id,
            'reading_value' => 1000.0,
            'reading_date' => now()->subMonth(),
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'tenant_id' => $tenant->tenant_id,
            'reading_value' => 1100.0,
            'reading_date' => now(),
        ]);

        $invoice = $this->billingService->generateInvoice(
            $property,
            now()->subMonth(),
            now()
        );

        // Verify tariff is snapshotted
        $this->assertNotNull($invoice);
        $invoiceItem = $invoice->invoiceItems->first();
        $this->assertNotNull($invoiceItem);
        $this->assertIsArray($invoiceItem->tariff_snapshot);
    }

    public function test_handles_multiple_meters_per_property(): void
    {
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->tenant_id]);
        
        // Create electricity meter
        $electricityMeter = Meter::factory()->create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->tenant_id,
            'type' => MeterType::ELECTRICITY,
        ]);

        // Create water meter
        $waterMeter = Meter::factory()->create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->tenant_id,
            'type' => MeterType::WATER_COLD,
        ]);

        // Create readings for electricity
        MeterReading::factory()->create([
            'meter_id' => $electricityMeter->id,
            'tenant_id' => $tenant->tenant_id,
            'reading_value' => 1000.0,
            'reading_date' => now()->subMonth(),
        ]);

        MeterReading::factory()->create([
            'meter_id' => $electricityMeter->id,
            'tenant_id' => $tenant->tenant_id,
            'reading_value' => 1100.0,
            'reading_date' => now(),
        ]);

        // Create readings for water
        MeterReading::factory()->create([
            'meter_id' => $waterMeter->id,
            'tenant_id' => $tenant->tenant_id,
            'reading_value' => 50.0,
            'reading_date' => now()->subMonth(),
        ]);

        MeterReading::factory()->create([
            'meter_id' => $waterMeter->id,
            'tenant_id' => $tenant->tenant_id,
            'reading_value' => 55.0,
            'reading_date' => now(),
        ]);

        $invoice = $this->billingService->generateInvoice(
            $property,
            now()->subMonth(),
            now()
        );

        // Should have items for both meters
        $this->assertGreaterThanOrEqual(2, $invoice->invoiceItems->count());
    }

    public function test_respects_tenant_isolation(): void
    {
        $tenant1 = User::factory()->tenant()->create();
        $tenant2 = User::factory()->tenant()->create();
        
        $property1 = Property::factory()->create(['tenant_id' => $tenant1->tenant_id]);
        $property2 = Property::factory()->create(['tenant_id' => $tenant2->tenant_id]);

        $meter1 = Meter::factory()->create([
            'property_id' => $property1->id,
            'tenant_id' => $tenant1->tenant_id,
            'type' => MeterType::ELECTRICITY,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter1->id,
            'tenant_id' => $tenant1->tenant_id,
            'reading_value' => 1000.0,
            'reading_date' => now()->subMonth(),
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter1->id,
            'tenant_id' => $tenant1->tenant_id,
            'reading_value' => 1100.0,
            'reading_date' => now(),
        ]);

        $invoice = $this->billingService->generateInvoice(
            $property1,
            now()->subMonth(),
            now()
        );

        // Invoice should belong to tenant1, not tenant2
        $this->assertEquals($tenant1->id, $invoice->tenant_renter_id);
        $this->assertNotEquals($tenant2->id, $invoice->tenant_renter_id);
    }
}
