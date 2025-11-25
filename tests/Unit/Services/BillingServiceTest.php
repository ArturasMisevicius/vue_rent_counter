<?php

namespace Tests\Unit\Services;

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Enums\ServiceType;
use App\Exceptions\BillingException;
use App\Exceptions\InvoiceAlreadyFinalizedException;
use App\Exceptions\MissingMeterReadingException;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BillingService;
use App\Services\GyvatukasCalculator;
use App\Services\MeterReadingService;
use App\Services\TariffResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BillingService $billingService;
    private User $user;
    private Tenant $tenant;
    private Property $property;
    private Building $building;

    protected function setUp(): void
    {
        parent::setUp();

        // Create service dependencies
        $tariffResolver = new TariffResolver();
        $gyvatukasCalculator = new GyvatukasCalculator();
        $meterReadingService = new MeterReadingService();

        $this->billingService = new BillingService(
            $tariffResolver,
            $gyvatukasCalculator,
            $meterReadingService
        );

        // Create test data
        $this->user = User::factory()->create(['tenant_id' => 1]);
        $this->building = Building::factory()->create(['tenant_id' => 1]);
        $this->property = Property::factory()->create([
            'tenant_id' => 1,
            'building_id' => $this->building->id,
        ]);
        $this->tenant = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => $this->property->id,
        ]);
    }

    /** @test */
    public function it_generates_invoice_with_electricity_consumption(): void
    {
        // Arrange
        $provider = Provider::factory()->create(['service_type' => ServiceType::ELECTRICITY]);
        $tariff = Tariff::factory()->create([
            'provider_id' => $provider->id,
            'configuration' => [
                'type' => 'flat',
                'rate' => 0.15,
            ],
            'active_from' => now()->subMonth(),
        ]);

        $meter = Meter::factory()->create([
            'tenant_id' => 1,
            'property_id' => $this->property->id,
            'type' => MeterType::ELECTRICITY,
            'supports_zones' => false,
        ]);

        $startReading = MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'value' => 1000,
            'reading_date' => now()->startOfMonth(),
            'entered_by' => $this->user->id,
        ]);

        $endReading = MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'value' => 1100,
            'reading_date' => now()->endOfMonth(),
            'entered_by' => $this->user->id,
        ]);

        // Act
        $invoice = $this->billingService->generateInvoice(
            $this->tenant,
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        // Assert
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals(InvoiceStatus::DRAFT, $invoice->status);
        $this->assertEquals($this->tenant->id, $invoice->tenant_renter_id);
        $this->assertCount(1, $invoice->items);

        $item = $invoice->items->first();
        $this->assertEquals(100, $item->quantity); // 1100 - 1000
        $this->assertEquals(0.15, $item->unit_price);
        $this->assertEquals(15.00, $item->total); // 100 * 0.15
        $this->assertEquals('kWh', $item->unit);
        $this->assertArrayHasKey('meter_id', $item->meter_reading_snapshot);
        $this->assertArrayHasKey('tariff_configuration', $item->meter_reading_snapshot);
    }

    /** @test */
    public function it_generates_invoice_with_water_consumption_including_supply_and_sewage(): void
    {
        // Arrange
        $provider = Provider::factory()->create(['service_type' => ServiceType::WATER]);
        $tariff = Tariff::factory()->create([
            'provider_id' => $provider->id,
            'configuration' => ['type' => 'flat', 'rate' => 0.97],
            'active_from' => now()->subMonth(),
        ]);

        $meter = Meter::factory()->create([
            'tenant_id' => 1,
            'property_id' => $this->property->id,
            'type' => MeterType::WATER_COLD,
            'supports_zones' => false,
        ]);

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'value' => 50,
            'reading_date' => now()->startOfMonth(),
            'entered_by' => $this->user->id,
        ]);

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'value' => 60,
            'reading_date' => now()->endOfMonth(),
            'entered_by' => $this->user->id,
        ]);

        // Act
        $invoice = $this->billingService->generateInvoice(
            $this->tenant,
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        // Assert
        $this->assertCount(2, $invoice->items); // Consumption + Fixed fee

        $consumptionItem = $invoice->items->first();
        $this->assertEquals(10, $consumptionItem->quantity); // 60 - 50
        $this->assertEquals('m³', $consumptionItem->unit);
        
        // Water total = (10 * 0.97) + (10 * 1.23) = 9.70 + 12.30 = 22.00
        $this->assertEquals(22.00, $consumptionItem->total);

        $fixedFeeItem = $invoice->items->last();
        $this->assertStringContainsString('Fixed Fee', $fixedFeeItem->description);
        $this->assertEquals(0.85, $fixedFeeItem->total);
    }

    /** @test */
    public function it_generates_invoice_with_multi_zone_electricity_meter(): void
    {
        // Arrange
        $provider = Provider::factory()->create(['service_type' => ServiceType::ELECTRICITY]);
        $tariff = Tariff::factory()->create([
            'provider_id' => $provider->id,
            'configuration' => [
                'type' => 'time_of_use',
                'zones' => [
                    ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
                    ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
                ],
            ],
            'active_from' => now()->subMonth(),
        ]);

        $meter = Meter::factory()->create([
            'tenant_id' => 1,
            'property_id' => $this->property->id,
            'type' => MeterType::ELECTRICITY,
            'supports_zones' => true,
        ]);

        // Day zone readings
        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'value' => 1000,
            'zone' => 'day',
            'reading_date' => now()->startOfMonth(),
            'entered_by' => $this->user->id,
        ]);

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'value' => 1080,
            'zone' => 'day',
            'reading_date' => now()->endOfMonth(),
            'entered_by' => $this->user->id,
        ]);

        // Night zone readings
        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'value' => 500,
            'zone' => 'night',
            'reading_date' => now()->startOfMonth(),
            'entered_by' => $this->user->id,
        ]);

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'value' => 520,
            'zone' => 'night',
            'reading_date' => now()->endOfMonth(),
            'entered_by' => $this->user->id,
        ]);

        // Act
        $invoice = $this->billingService->generateInvoice(
            $this->tenant,
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        // Assert
        $this->assertCount(2, $invoice->items); // Day zone + Night zone

        $dayItem = $invoice->items->where('meter_reading_snapshot.zone', 'day')->first();
        $this->assertNotNull($dayItem);
        $this->assertEquals(80, $dayItem->quantity);

        $nightItem = $invoice->items->where('meter_reading_snapshot.zone', 'night')->first();
        $this->assertNotNull($nightItem);
        $this->assertEquals(20, $nightItem->quantity);
    }

    /** @test */
    public function it_throws_exception_when_tenant_has_no_property(): void
    {
        // Arrange
        $tenantWithoutProperty = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => null,
        ]);

        // Act & Assert
        $this->expectException(BillingException::class);
        $this->expectExceptionMessage('has no associated property');

        $this->billingService->generateInvoice(
            $tenantWithoutProperty,
            now()->startOfMonth(),
            now()->endOfMonth()
        );
    }

    /** @test */
    public function it_throws_exception_when_property_has_no_meters(): void
    {
        // Arrange - property already exists with no meters

        // Act & Assert
        $this->expectException(BillingException::class);
        $this->expectExceptionMessage('has no meters');

        $this->billingService->generateInvoice(
            $this->tenant,
            now()->startOfMonth(),
            now()->endOfMonth()
        );
    }

    /** @test */
    public function it_throws_exception_when_meter_readings_are_missing(): void
    {
        // Arrange
        $provider = Provider::factory()->create(['service_type' => ServiceType::ELECTRICITY]);
        Tariff::factory()->create([
            'provider_id' => $provider->id,
            'configuration' => ['type' => 'flat', 'rate' => 0.15],
            'active_from' => now()->subMonth(),
        ]);

        $meter = Meter::factory()->create([
            'tenant_id' => 1,
            'property_id' => $this->property->id,
            'type' => MeterType::ELECTRICITY,
        ]);

        // No readings created

        // Act & Assert
        $this->expectException(MissingMeterReadingException::class);

        $this->billingService->generateInvoice(
            $this->tenant,
            now()->startOfMonth(),
            now()->endOfMonth()
        );
    }

    /** @test */
    public function it_finalizes_draft_invoice(): void
    {
        // Arrange
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $this->tenant->id,
            'status' => InvoiceStatus::DRAFT,
            'finalized_at' => null,
        ]);

        // Act
        $finalizedInvoice = $this->billingService->finalizeInvoice($invoice);

        // Assert
        $this->assertEquals(InvoiceStatus::FINALIZED, $finalizedInvoice->status);
        $this->assertNotNull($finalizedInvoice->finalized_at);
        $this->assertTrue($finalizedInvoice->isFinalized());
    }

    /** @test */
    public function it_throws_exception_when_finalizing_already_finalized_invoice(): void
    {
        // Arrange
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $this->tenant->id,
            'status' => InvoiceStatus::FINALIZED,
            'finalized_at' => now(),
        ]);

        // Act & Assert
        $this->expectException(InvoiceAlreadyFinalizedException::class);

        $this->billingService->finalizeInvoice($invoice);
    }

    /** @test */
    public function it_snapshots_tariff_configuration_in_invoice_items(): void
    {
        // Arrange
        $provider = Provider::factory()->create(['service_type' => ServiceType::ELECTRICITY]);
        $tariff = Tariff::factory()->create([
            'provider_id' => $provider->id,
            'configuration' => [
                'type' => 'flat',
                'rate' => 0.15,
                'currency' => 'EUR',
            ],
            'active_from' => now()->subMonth(),
        ]);

        $meter = Meter::factory()->create([
            'tenant_id' => 1,
            'property_id' => $this->property->id,
            'type' => MeterType::ELECTRICITY,
        ]);

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'value' => 1000,
            'reading_date' => now()->startOfMonth(),
            'entered_by' => $this->user->id,
        ]);

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'value' => 1100,
            'reading_date' => now()->endOfMonth(),
            'entered_by' => $this->user->id,
        ]);

        // Act
        $invoice = $this->billingService->generateInvoice(
            $this->tenant,
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        // Assert - Verify tariff is snapshotted
        $item = $invoice->items->first();
        $snapshot = $item->meter_reading_snapshot;

        $this->assertArrayHasKey('tariff_id', $snapshot);
        $this->assertEquals($tariff->id, $snapshot['tariff_id']);
        $this->assertArrayHasKey('tariff_configuration', $snapshot);
        $this->assertEquals($tariff->configuration, $snapshot['tariff_configuration']);

        // Now change the tariff
        $tariff->update(['configuration' => ['type' => 'flat', 'rate' => 0.25]]);

        // Verify invoice item still has old configuration
        $item->refresh();
        $this->assertEquals(0.15, $item->meter_reading_snapshot['tariff_configuration']['rate']);
    }

    /** @test */
    public function it_calculates_correct_total_amount_for_invoice(): void
    {
        // Arrange
        $electricityProvider = Provider::factory()->create(['service_type' => ServiceType::ELECTRICITY]);
        Tariff::factory()->create([
            'provider_id' => $electricityProvider->id,
            'configuration' => ['type' => 'flat', 'rate' => 0.15],
            'active_from' => now()->subMonth(),
        ]);

        $waterProvider = Provider::factory()->create(['service_type' => ServiceType::WATER]);
        Tariff::factory()->create([
            'provider_id' => $waterProvider->id,
            'configuration' => ['type' => 'flat', 'rate' => 0.97],
            'active_from' => now()->subMonth(),
        ]);

        $electricityMeter = Meter::factory()->create([
            'tenant_id' => 1,
            'property_id' => $this->property->id,
            'type' => MeterType::ELECTRICITY,
        ]);

        $waterMeter = Meter::factory()->create([
            'tenant_id' => 1,
            'property_id' => $this->property->id,
            'type' => MeterType::WATER_COLD,
        ]);

        // Electricity readings: 100 kWh consumption
        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $electricityMeter->id,
            'value' => 1000,
            'reading_date' => now()->startOfMonth(),
            'entered_by' => $this->user->id,
        ]);

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $electricityMeter->id,
            'value' => 1100,
            'reading_date' => now()->endOfMonth(),
            'entered_by' => $this->user->id,
        ]);

        // Water readings: 10 m³ consumption
        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $waterMeter->id,
            'value' => 50,
            'reading_date' => now()->startOfMonth(),
            'entered_by' => $this->user->id,
        ]);

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $waterMeter->id,
            'value' => 60,
            'reading_date' => now()->endOfMonth(),
            'entered_by' => $this->user->id,
        ]);

        // Act
        $invoice = $this->billingService->generateInvoice(
            $this->tenant,
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        // Assert
        // Electricity: 100 * 0.15 = 15.00
        // Water: (10 * 0.97) + (10 * 1.23) = 22.00
        // Water fixed fee: 0.85
        // Total: 15.00 + 22.00 + 0.85 = 37.85
        $this->assertEquals(37.85, $invoice->total_amount);
    }
}
