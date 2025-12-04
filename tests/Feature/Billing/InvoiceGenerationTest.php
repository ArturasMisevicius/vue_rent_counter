<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Enums\PropertyType;
use App\Enums\ServiceType;
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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Invoice Generation Integration Tests (Phase 6)
 *
 * Tests the end-to-end billing pipeline:
 * Input (MeterReadings) -> Processing (BillingService) -> Output (Invoice + InvoiceItems)
 *
 * This validates that all previous phases work together correctly:
 * - Phase 1: Models (Property, Meter, MeterReading, Invoice, InvoiceItem)
 * - Phase 3: Services (BillingService, TariffResolver)
 * - Phase 5: Input Logic (MeterReading validation)
 * - Phase 6: Output (Invoice generation with correct totals)
 *
 * @group integration
 * @group billing
 * @group phase-6
 */
class InvoiceGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed providers (required for tariff resolution)
        $this->seed(\Database\Seeders\ProvidersSeeder::class);

        // Set tenant context for multi-tenancy
        session(['tenant_id' => 1]);
    }

    // ========================================
    // CORE INTEGRATION TEST (Phase 6 Requirement)
    // ========================================

    /** @test */
    public function it_generates_invoice_from_meter_readings_with_flat_rate_tariff(): void
    {
        // ============================================
        // SETUP: Create all required entities
        // ============================================

        // Create Property
        $property = Property::factory()->create([
            'tenant_id' => 1,
            'type' => PropertyType::APARTMENT,
        ]);

        // Create Tenant (renter)
        $tenant = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
        ]);

        // Create Meter
        $meter = Meter::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
            'supports_zones' => false,
        ]);

        // Create User for entering readings
        $user = User::factory()->create(['tenant_id' => 1]);

        // Create Tariff (Flat Rate: 0.20 EUR/kWh)
        $provider = Provider::where('service_type', ServiceType::ELECTRICITY)->first();
        $tariff = Tariff::factory()->create([
            'provider_id' => $provider->id,
            'configuration' => [
                'type' => 'flat',
                'rate' => 0.20, // 0.20 EUR per kWh
                'currency' => 'EUR',
            ],
            'active_from' => Carbon::parse('2024-01-01')->subMonth(),
            'active_until' => null,
        ]);

        // ============================================
        // INPUT: Create two MeterReadings
        // Jan 1st = 100, Feb 1st = 150
        // Consumption = 50 kWh
        // ============================================

        $periodStart = Carbon::parse('2024-01-01');
        $periodEnd = Carbon::parse('2024-02-01');

        $reading1 = MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'reading_date' => $periodStart,
            'value' => 100.00,
            'entered_by' => $user->id,
        ]);

        $reading2 = MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'reading_date' => $periodEnd,
            'value' => 150.00,
            'entered_by' => $user->id,
        ]);

        // ============================================
        // ACTION: Generate Invoice
        // ============================================

        $billingService = app(BillingService::class);
        $invoice = $billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        // ============================================
        // ASSERTIONS: Verify Output (The Critical Part)
        // ============================================

        // 1. Assert Invoice exists in database
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'tenant_renter_id' => $tenant->id,
        ]);

        // 2. Assert Invoice is returned as an object
        $this->assertInstanceOf(Invoice::class, $invoice);

        // 3. Assert Invoice status is DRAFT
        $this->assertEquals(InvoiceStatus::DRAFT, $invoice->status);

        // 4. Assert Invoice billing period is correct
        $this->assertEquals($periodStart->toDateString(), $invoice->billing_period_start->toDateString());
        $this->assertEquals($periodEnd->toDateString(), $invoice->billing_period_end->toDateString());

        // 5. Assert Invoice total equals: Consumption (50) * Rate (0.20) = 10.00 EUR
        $expectedTotal = 50 * 0.20; // 10.00
        $this->assertEquals($expectedTotal, $invoice->total_amount);

        // 6. Assert InvoiceItems exist (at least one)
        $this->assertGreaterThan(0, $invoice->items->count());

        // 7. Assert InvoiceItem exists in database
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
        ]);

        // 8. Find the electricity consumption item
        // Note: InvoiceItems use 'quantity' for consumption, not separate 'consumption' field
        $consumptionItem = $invoice->items->filter(function ($item) {
            return $item->quantity == 50.00 && $item->unit_price == 0.20;
        })->first();

        // 9. Assert consumption item has correct values
        $this->assertNotNull($consumptionItem, 'Expected invoice item with 50 quantity and 0.20 unit_price');
        $this->assertEquals(50.00, $consumptionItem->quantity); // Consumption stored as quantity
        $this->assertEquals(0.20, $consumptionItem->unit_price); // Rate per unit
        $this->assertEquals($expectedTotal, $consumptionItem->total); // Total amount
    }

    // ========================================
    // ADDITIONAL INTEGRATION TESTS
    // ========================================

    /** @test */
    public function it_generates_invoice_with_multiple_meters(): void
    {
        // Setup
        $property = Property::factory()->create([
            'tenant_id' => 1,
            'type' => PropertyType::APARTMENT,
        ]);

        $tenant = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
        ]);

        $user = User::factory()->create(['tenant_id' => 1]);

        // Create two meters: Electricity and Water (Cold)
        $electricityMeter = Meter::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
            'supports_zones' => false,
        ]);

        $waterMeter = Meter::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
            'type' => MeterType::WATER_COLD,
            'supports_zones' => false,
        ]);

        // Create tariffs for both services
        $electricityProvider = Provider::where('service_type', ServiceType::ELECTRICITY)->first();
        Tariff::factory()->create([
            'provider_id' => $electricityProvider->id,
            'configuration' => [
                'type' => 'flat',
                'rate' => 0.15,
                'currency' => 'EUR',
            ],
            'active_from' => Carbon::parse('2024-01-01')->subMonth(),
            'active_until' => null,
        ]);

        $waterProvider = Provider::where('service_type', ServiceType::WATER)->first();
        Tariff::factory()->create([
            'provider_id' => $waterProvider->id,
            'configuration' => [
                'type' => 'flat',
                'rate' => 1.50,
                'currency' => 'EUR',
            ],
            'active_from' => Carbon::parse('2024-01-01')->subMonth(),
            'active_until' => null,
        ]);

        // Create readings for electricity: 100 kWh consumption
        $periodStart = Carbon::parse('2024-01-01');
        $periodEnd = Carbon::parse('2024-01-31');

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $electricityMeter->id,
            'reading_date' => $periodStart,
            'value' => 1000.00,
            'entered_by' => $user->id,
        ]);

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $electricityMeter->id,
            'reading_date' => $periodEnd,
            'value' => 1100.00,
            'entered_by' => $user->id,
        ]);

        // Create readings for water: 10 m³ consumption
        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $waterMeter->id,
            'reading_date' => $periodStart,
            'value' => 50.00,
            'entered_by' => $user->id,
        ]);

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $waterMeter->id,
            'reading_date' => $periodEnd,
            'value' => 60.00,
            'entered_by' => $user->id,
        ]);

        // Action: Generate invoice
        $billingService = app(BillingService::class);
        $invoice = $billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        // Assertions
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals(InvoiceStatus::DRAFT, $invoice->status);

        // Should have multiple invoice items (water creates multiple items: supply + sewage + fixed fee)
        $this->assertGreaterThanOrEqual(2, $invoice->items->count());

        // Find electricity item: 100 kWh * 0.15 = 15.00 EUR
        $electricityItem = $invoice->items->filter(function ($item) {
            return $item->quantity == 100.00 && $item->unit_price == 0.15;
        })->first();
        $this->assertNotNull($electricityItem, 'Expected electricity item with 100 quantity and 0.15 unit_price');
        $this->assertEquals(15.00, $electricityItem->total);

        // Find water consumption item: 10 m³
        $waterItem = $invoice->items->filter(function ($item) {
            return $item->quantity == 10.00 && str_contains($item->description ?? '', 'Water');
        })->first();
        $this->assertNotNull($waterItem, 'Expected water item with 10 quantity');

        // Total should include both utilities
        $this->assertGreaterThan(0, $invoice->total_amount);
    }

    /** @test */
    public function it_generates_invoice_with_zero_consumption(): void
    {
        // Setup
        $property = Property::factory()->create([
            'tenant_id' => 1,
            'type' => PropertyType::APARTMENT,
        ]);

        $tenant = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
        ]);

        $meter = Meter::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
            'supports_zones' => false,
        ]);

        $user = User::factory()->create(['tenant_id' => 1]);

        // Create tariff
        $provider = Provider::where('service_type', ServiceType::ELECTRICITY)->first();
        Tariff::factory()->create([
            'provider_id' => $provider->id,
            'configuration' => [
                'type' => 'flat',
                'rate' => 0.15,
                'currency' => 'EUR',
            ],
            'active_from' => Carbon::parse('2024-01-01')->subMonth(),
            'active_until' => null,
        ]);

        // Create readings with ZERO consumption (same value)
        $periodStart = Carbon::parse('2024-01-01');
        $periodEnd = Carbon::parse('2024-01-31');

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'reading_date' => $periodStart,
            'value' => 1000.00,
            'entered_by' => $user->id,
        ]);

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'reading_date' => $periodEnd,
            'value' => 1000.00, // Same value = 0 consumption
            'entered_by' => $user->id,
        ]);

        // Action: Generate invoice
        $billingService = app(BillingService::class);
        $invoice = $billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        // Assertions
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals(InvoiceStatus::DRAFT, $invoice->status);

        // BillingService may skip items with zero consumption or create them with 0 value
        // Either behavior is acceptable
        if ($invoice->items->count() > 0) {
            // If item was created, it should have zero consumption and total
            $invoiceItem = $invoice->items->first();
            $this->assertEquals(0.00, $invoiceItem->quantity);
            $this->assertEquals(0.00, $invoiceItem->total);
        }

        // Invoice total should be 0.00 regardless
        $this->assertEquals(0.00, $invoice->total_amount);
    }

    /** @test */
    public function it_throws_exception_when_meter_readings_are_missing(): void
    {
        // Setup
        $property = Property::factory()->create([
            'tenant_id' => 1,
            'type' => PropertyType::APARTMENT,
        ]);

        $tenant = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
        ]);

        $meter = Meter::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
            'supports_zones' => false,
        ]);

        // Create tariff
        $provider = Provider::where('service_type', ServiceType::ELECTRICITY)->first();
        Tariff::factory()->create([
            'provider_id' => $provider->id,
            'configuration' => [
                'type' => 'flat',
                'rate' => 0.15,
                'currency' => 'EUR',
            ],
            'active_from' => Carbon::parse('2024-01-01')->subMonth(),
            'active_until' => null,
        ]);

        // NO meter readings created

        // Action & Assertion: Should throw MissingMeterReadingException
        $periodStart = Carbon::parse('2024-01-01');
        $periodEnd = Carbon::parse('2024-01-31');

        $billingService = app(BillingService::class);

        $this->expectException(\App\Exceptions\MissingMeterReadingException::class);

        $billingService->generateInvoice($tenant, $periodStart, $periodEnd);
    }

    /** @test */
    public function invoice_items_snapshot_tariff_rates(): void
    {
        // Setup
        $property = Property::factory()->create([
            'tenant_id' => 1,
            'type' => PropertyType::APARTMENT,
        ]);

        $tenant = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
        ]);

        $meter = Meter::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
            'supports_zones' => false,
        ]);

        $user = User::factory()->create(['tenant_id' => 1]);

        // Create tariff with specific rate
        $provider = Provider::where('service_type', ServiceType::ELECTRICITY)->first();
        $originalRate = 0.18;
        $tariff = Tariff::factory()->create([
            'provider_id' => $provider->id,
            'configuration' => [
                'type' => 'flat',
                'rate' => $originalRate,
                'currency' => 'EUR',
            ],
            'active_from' => Carbon::parse('2024-01-01')->subMonth(),
            'active_until' => null,
        ]);

        // Create readings
        $periodStart = Carbon::parse('2024-01-01');
        $periodEnd = Carbon::parse('2024-01-31');

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'reading_date' => $periodStart,
            'value' => 100.00,
            'entered_by' => $user->id,
        ]);

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'reading_date' => $periodEnd,
            'value' => 200.00,
            'entered_by' => $user->id,
        ]);

        // Action: Generate invoice
        $billingService = app(BillingService::class);
        $invoice = $billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        // Verify invoice item was created with snapshotted rate
        $consumptionItem = $invoice->items->filter(function ($item) {
            return $item->quantity == 100.00 && $item->unit_price == 0.18;
        })->first();

        $this->assertNotNull($consumptionItem, 'Expected invoice item with 100 quantity and 0.18 unit_price');
        $this->assertEquals(100.00, $consumptionItem->quantity); // Consumption
        $this->assertEquals(18.00, $consumptionItem->total); // 100 * 0.18

        // Now change the tariff rate (simulate price change)
        $tariff->update([
            'configuration' => [
                'type' => 'flat',
                'rate' => 0.25, // NEW RATE
                'currency' => 'EUR',
            ],
        ]);

        // Re-fetch the invoice item
        $consumptionItem->refresh();

        // Assertion: Invoice item should STILL have the old rate (snapshotted)
        // The unit_price and total should remain unchanged
        $this->assertEquals(0.18, $consumptionItem->unit_price); // Still 0.18, not 0.25
        $this->assertEquals(18.00, $consumptionItem->total); // Still 18.00, not 25.00

        // The tariff rate is snapshotted in the invoice item
        // so changes to the tariff don't affect existing invoices
    }

    /** @test */
    public function it_creates_invoice_with_correct_due_date(): void
    {
        // Setup
        $property = Property::factory()->create([
            'tenant_id' => 1,
            'type' => PropertyType::APARTMENT,
        ]);

        $tenant = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
        ]);

        $meter = Meter::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
            'supports_zones' => false,
        ]);

        $user = User::factory()->create(['tenant_id' => 1]);

        // Create tariff
        $provider = Provider::where('service_type', ServiceType::ELECTRICITY)->first();
        Tariff::factory()->create([
            'provider_id' => $provider->id,
            'configuration' => [
                'type' => 'flat',
                'rate' => 0.15,
                'currency' => 'EUR',
            ],
            'active_from' => Carbon::parse('2024-01-01')->subMonth(),
            'active_until' => null,
        ]);

        // Create readings
        $periodStart = Carbon::parse('2024-01-01');
        $periodEnd = Carbon::parse('2024-01-31');

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'reading_date' => $periodStart,
            'value' => 100.00,
            'entered_by' => $user->id,
        ]);

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'reading_date' => $periodEnd,
            'value' => 150.00,
            'entered_by' => $user->id,
        ]);

        // Action: Generate invoice
        $billingService = app(BillingService::class);
        $invoice = $billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        // Assertion: Due date should be 14 days after period end (default config)
        $expectedDueDate = $periodEnd->copy()->addDays(14);
        $this->assertEquals($expectedDueDate->toDateString(), $invoice->due_date->toDateString());
    }
}
