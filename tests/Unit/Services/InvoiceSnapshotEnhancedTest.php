<?php

namespace Tests\Unit\Services;

use App\Enums\ApprovalStatus;
use App\Enums\AutomationLevel;
use App\Models\BillingRecord;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\UtilityService;
use App\Services\InvoiceSnapshotService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceSnapshotEnhancedTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceSnapshotService $service;
    private Invoice $invoice;
    private Tenant $tenant;
    private Property $property;
    private Currency $baseCurrency;
    private Currency $targetCurrency;
    private ExchangeRate $exchangeRate;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(InvoiceSnapshotService::class);
        
        // Create test currencies
        $this->baseCurrency = Currency::factory()->create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_default' => true,
        ]);
        
        $this->targetCurrency = Currency::factory()->create([
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => '€',
            'is_default' => false,
        ]);
        
        // Create exchange rate
        $this->exchangeRate = ExchangeRate::factory()->create([
            'from_currency_id' => $this->baseCurrency->id,
            'to_currency_id' => $this->targetCurrency->id,
            'rate' => 0.85,
            'effective_date' => Carbon::now()->subDay(),
        ]);
        
        // Create property
        $this->property = Property::factory()->create([
            'address' => '123 Test Street',
            'type' => 'apartment',
            'size' => 100.0,
        ]);
        
        // Create tenant
        $this->tenant = Tenant::factory()->create([
            'property_id' => $this->property->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
        ]);
        
        // Create utility services
        UtilityService::factory()->create([
            'property_id' => $this->property->id,
            'name' => 'Electricity',
            'type' => 'electricity',
            'unit' => 'kWh',
            'base_rate' => 0.50,
        ]);
        
        UtilityService::factory()->create([
            'property_id' => $this->property->id,
            'name' => 'Gas',
            'type' => 'gas',
            'unit' => 'm³',
            'base_rate' => 1.00,
        ]);
        
        // Create invoice
        $this->invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'billing_period_start' => Carbon::now()->startOfMonth(),
            'billing_period_end' => Carbon::now()->endOfMonth(),
            'due_date' => Carbon::now()->addDays(30),
            'amount' => 100.00,
            'currency' => 'USD',
            'status' => 'pending',
            'approval_status' => ApprovalStatus::PENDING,
            'automation_level' => AutomationLevel::SEMI_AUTOMATED,
        ]);
        
        // Create billing records
        BillingRecord::factory()->create([
            'invoice_id' => $this->invoice->id,
            'tenant_id' => $this->tenant->id,
            'property_id' => $this->property->id,
            'service_type' => 'electricity',
            'consumption' => 100.0,
            'rate' => 0.50,
            'amount' => 50.00,
            'billing_period_start' => Carbon::now()->startOfMonth(),
            'billing_period_end' => Carbon::now()->endOfMonth(),
        ]);
        
        BillingRecord::factory()->create([
            'invoice_id' => $this->invoice->id,
            'tenant_id' => $this->tenant->id,
            'property_id' => $this->property->id,
            'service_type' => 'gas',
            'consumption' => 50.0,
            'rate' => 1.00,
            'amount' => 50.00,
            'billing_period_start' => Carbon::now()->startOfMonth(),
            'billing_period_end' => Carbon::now()->endOfMonth(),
        ]);
    }

    public function test_creates_enhanced_snapshot_with_currency_conversion()
    {
        // Act
        $snapshot = $this->service->createEnhancedSnapshot($this->invoice, 'EUR');
        
        // Assert
        $this->assertArrayHasKey('invoice_id', $snapshot);
        $this->assertArrayHasKey('tenant_id', $snapshot);
        $this->assertArrayHasKey('billing_period_start', $snapshot);
        $this->assertArrayHasKey('billing_period_end', $snapshot);
        $this->assertArrayHasKey('due_date', $snapshot);
        $this->assertArrayHasKey('amount', $snapshot);
        $this->assertArrayHasKey('currency', $snapshot);
        $this->assertArrayHasKey('status', $snapshot);
        $this->assertArrayHasKey('approval_status', $snapshot);
        $this->assertArrayHasKey('automation_level', $snapshot);
        $this->assertArrayHasKey('approval_metadata', $snapshot);
        $this->assertArrayHasKey('notes', $snapshot);
        $this->assertArrayHasKey('billing_records', $snapshot);
        $this->assertArrayHasKey('created_at', $snapshot);
        $this->assertArrayHasKey('updated_at', $snapshot);
        
        // Enhanced fields
        $this->assertArrayHasKey('converted_amount', $snapshot);
        $this->assertArrayHasKey('target_currency', $snapshot);
        $this->assertArrayHasKey('exchange_rate', $snapshot);
        $this->assertArrayHasKey('conversion_date', $snapshot);
        $this->assertArrayHasKey('tenant_info', $snapshot);
        $this->assertArrayHasKey('property_info', $snapshot);
        $this->assertArrayHasKey('utility_services', $snapshot);
        $this->assertArrayHasKey('analytics', $snapshot);
        
        // Verify converted amount
        $this->assertEquals(85.0, $snapshot['converted_amount']);
        $this->assertEquals('EUR', $snapshot['target_currency']);
        $this->assertEquals(0.85, $snapshot['exchange_rate']);
        
        // Verify tenant info
        $this->assertArrayHasKey('name', $snapshot['tenant_info']);
        $this->assertArrayHasKey('email', $snapshot['tenant_info']);
        
        // Verify property info
        $this->assertArrayHasKey('address', $snapshot['property_info']);
        $this->assertArrayHasKey('type', $snapshot['property_info']);
        
        // Verify utility services
        $this->assertIsArray($snapshot['utility_services']);
        
        // Verify analytics
        $this->assertArrayHasKey('total_consumption', $snapshot['analytics']);
        $this->assertArrayHasKey('average_rate', $snapshot['analytics']);
        $this->assertArrayHasKey('service_breakdown', $snapshot['analytics']);
    }

    public function test_enhanced_snapshot_includes_tenant_information()
    {
        // Act
        $snapshot = $this->service->createEnhancedSnapshot($this->invoice, 'EUR');
        
        // Assert
        $this->assertArrayHasKey('tenant_info', $snapshot);
        $tenantInfo = $snapshot['tenant_info'];
        
        $this->assertEquals($this->tenant->name, $tenantInfo['name']);
        $this->assertEquals($this->tenant->email, $tenantInfo['email']);
        $this->assertEquals($this->tenant->phone, $tenantInfo['phone']);
        $this->assertEquals($this->tenant->id, $tenantInfo['id']);
    }
    
    public function test_enhanced_snapshot_includes_property_information()
    {
        // Act
        $snapshot = $this->service->createEnhancedSnapshot($this->invoice, 'EUR');
        
        // Assert
        $this->assertArrayHasKey('property_info', $snapshot);
        $propertyInfo = $snapshot['property_info'];
        
        $this->assertEquals($this->property->address, $propertyInfo['address']);
        $this->assertEquals($this->property->type, $propertyInfo['type']);
        $this->assertEquals($this->property->size, $propertyInfo['size']);
        $this->assertEquals($this->property->id, $propertyInfo['id']);
    }
    
    public function test_enhanced_snapshot_includes_utility_services()
    {
        // Act
        $snapshot = $this->service->createEnhancedSnapshot($this->invoice, 'EUR');
        
        // Assert
        $this->assertArrayHasKey('utility_services', $snapshot);
        $utilityServices = $snapshot['utility_services'];
        
        $this->assertIsArray($utilityServices);
        $this->assertCount(2, $utilityServices); // We created 2 utility services
        
        foreach ($utilityServices as $service) {
            $this->assertArrayHasKey('id', $service);
            $this->assertArrayHasKey('name', $service);
            $this->assertArrayHasKey('type', $service);
            $this->assertArrayHasKey('unit', $service);
            $this->assertArrayHasKey('base_rate', $service);
        }
    }
    
    public function test_enhanced_snapshot_includes_analytics()
    {
        // Act
        $snapshot = $this->service->createEnhancedSnapshot($this->invoice, 'EUR');
        
        // Assert
        $this->assertArrayHasKey('analytics', $snapshot);
        $analytics = $snapshot['analytics'];
        
        $this->assertArrayHasKey('total_consumption', $analytics);
        $this->assertArrayHasKey('average_rate', $analytics);
        $this->assertArrayHasKey('service_breakdown', $analytics);
        
        // Verify calculations
        $this->assertEquals(150.0, $analytics['total_consumption']); // 100 + 50
        $this->assertEquals(0.75, $analytics['average_rate']); // (0.5 + 1.0) / 2
        
        $this->assertIsArray($analytics['service_breakdown']);
        $this->assertCount(2, $analytics['service_breakdown']);
    }

    public function test_enhanced_snapshot_handles_same_currency()
    {
        // Act - Convert to same currency
        $snapshot = $this->service->createEnhancedSnapshot($this->invoice, 'USD');
        
        // Assert
        $this->assertEquals(100.0, $snapshot['converted_amount']);
        $this->assertEquals('USD', $snapshot['target_currency']);
        $this->assertEquals(1.0, $snapshot['exchange_rate']);
    }

    public function test_enhanced_snapshot_service_breakdown_accuracy()
    {
        // Act
        $snapshot = $this->service->createEnhancedSnapshot($this->invoice, 'EUR');
        
        // Assert
        $serviceBreakdown = $snapshot['analytics']['service_breakdown'];
        
        // Find electricity service
        $electricityService = collect($serviceBreakdown)->firstWhere('service_type', 'electricity');
        $this->assertNotNull($electricityService);
        $this->assertEquals(100.0, $electricityService['total_consumption']);
        $this->assertEquals(50.0, $electricityService['total_amount']);
        $this->assertEquals(0.5, $electricityService['average_rate']);
        
        // Find gas service
        $gasService = collect($serviceBreakdown)->firstWhere('service_type', 'gas');
        $this->assertNotNull($gasService);
        $this->assertEquals(50.0, $gasService['total_consumption']);
        $this->assertEquals(50.0, $gasService['total_amount']);
        $this->assertEquals(1.0, $gasService['average_rate']);
    }

    public function test_enhanced_snapshot_with_no_billing_records()
    {
        // Create invoice without billing records
        $emptyInvoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'amount' => 0.00,
            'currency' => 'USD',
        ]);
        
        // Act
        $snapshot = $this->service->createEnhancedSnapshot($emptyInvoice, 'EUR');
        
        // Assert
        $this->assertEquals(0.0, $snapshot['analytics']['total_consumption']);
        $this->assertEquals(0, $snapshot['analytics']['average_rate']);
        $this->assertEmpty($snapshot['analytics']['service_breakdown']);
    }

    public function test_enhanced_snapshot_conversion_date_format()
    {
        // Act
        $snapshot = $this->service->createEnhancedSnapshot($this->invoice, 'EUR');
        
        // Assert
        $this->assertArrayHasKey('conversion_date', $snapshot);
        $this->assertInstanceOf(Carbon::class, $snapshot['conversion_date']);
    }
}