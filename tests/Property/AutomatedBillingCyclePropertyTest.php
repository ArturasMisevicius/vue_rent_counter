<?php

declare(strict_types=1);

namespace Tests\Property;

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Models\Tenant;
use App\Models\UtilityService;
use App\Services\AutomatedBillingEngine;
use App\ValueObjects\BillingOptions;
use App\ValueObjects\BillingPeriod;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Property-based tests for automated billing cycle execution.
 * 
 * Validates that the automated billing engine correctly processes
 * billing cycles across different scenarios, schedules, and configurations
 * while maintaining data integrity and business rule compliance.
 * 
 * **Property 6: Automated Billing Cycle Execution**
 * 
 * Tests Requirements:
 * - 7.1: Monthly, quarterly, and custom period schedules
 * - 7.2: Automatic reading collection integration
 * - 7.3: Error handling and graceful degradation
 * - 7.4: Invoice generation with proper status management
 * - 7.5: Audit trail and logging for all operations
 * 
 * @package Tests\Property
 * @group property-tests
 * @group automated-billing
 * @covers \App\Services\AutomatedBillingEngine
 */
final class AutomatedBillingCyclePropertyTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private AutomatedBillingEngine $billingEngine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->billingEngine = app(AutomatedBillingEngine::class);
    }

    /**
     * Property: Billing cycle execution generates correct number of invoices
     * for all properties with active service configurations.
     * 
     * @test
     */
    public function billing_cycle_generates_invoices_for_all_active_properties(): void
    {
        // Run multiple scenarios to test different configurations
        for ($iteration = 1; $iteration <= 50; $iteration++) {
            // Clean up data from previous iteration
            Invoice::truncate();
            MeterReading::truncate();
            Meter::truncate();
            ServiceConfiguration::truncate();
            Property::truncate();
            Tenant::truncate();
            
            // Generate random test data
            $tenantCount = rand(1, 5);
            $propertiesPerTenant = rand(1, 4);
            $metersPerProperty = rand(1, 3);
            
            $ownerTenants = Tenant::factory()->count($tenantCount)->create();
            $totalProperties = 0;
            $totalActiveConfigurations = 0;
            $totalRenterTenants = 0;
            
            foreach ($ownerTenants as $ownerTenant) {
                $properties = Property::factory()
                    ->count($propertiesPerTenant)
                    ->create([
                        'tenant_id' => $ownerTenant->tenant_id,
                    ]);
                
                $totalProperties += $propertiesPerTenant;
                
                foreach ($properties as $property) {
                    // Create a renter tenant for this property
                    $renterTenant = Tenant::factory()->create([
                        'property_id' => $property->id,
                    ]);
                    $totalRenterTenants++;
                    
                    // Create meters for each property
                    $meters = Meter::factory()
                        ->count($metersPerProperty)
                        ->create([
                            'property_id' => $property->id,
                            'type' => $this->faker->randomElement([
                                MeterType::ELECTRICITY,
                                MeterType::WATER_COLD,
                                MeterType::WATER_HOT,
                                MeterType::HEATING,
                            ]),
                        ]);
                    
                    // Create service configurations (some active, some inactive)
                    $activeConfigCount = rand(1, 2);
                    for ($i = 0; $i < $activeConfigCount; $i++) {
                        $utilityService = UtilityService::factory()->create([
                            'service_type_bridge' => ServiceType::ELECTRICITY,
                            'default_pricing_model' => PricingModel::FLAT,
                        ]);
                        
                        ServiceConfiguration::factory()
                            ->create([
                                'property_id' => $property->id,
                                'utility_service_id' => $utilityService->id,
                                'is_active' => true,
                                'effective_from' => now()->subMonth(),
                            ]);
                        
                        $totalActiveConfigurations++;
                    }
                    
                    // Create meter readings for the billing period
                    foreach ($meters as $meter) {
                        MeterReading::factory()
                            ->create([
                                'meter_id' => $meter->id,
                                'reading_date' => now()->subDays(rand(1, 30)),
                                'value' => rand(100, 1000),
                            ]);
                    }
                }
            }
            
            // Execute billing cycle
            $billingPeriod = BillingPeriod::currentMonth();
            $options = BillingOptions::fromArray(['create_zero_invoices' => true]);
            
            $result = $this->billingEngine->processBillingCycle($billingPeriod, $options);
            
            // Debug: Check result details
            echo "\nIteration {$iteration}: Billing result: processed={$result->getProcessedTenants()}, generated={$result->getGeneratedInvoices()}, success_rate={$result->getSuccessRate()}%\n";
            if ($result->hasWarnings()) {
                echo "Warnings: " . implode(', ', $result->getWarnings()) . "\n";
            }
            if ($result->hasErrors()) {
                echo "Errors: " . implode(', ', $result->getErrors()) . "\n";
            }
            
            // Debug: Check what invoices were actually created
            $allInvoices = Invoice::all();
            echo "Total invoices in database: {$allInvoices->count()}\n";
            foreach ($allInvoices as $invoice) {
                echo "  Invoice {$invoice->id}: tenant_renter_id={$invoice->tenant_renter_id}, amount={$invoice->total_amount}, period_start={$invoice->billing_period_start}, period_end={$invoice->billing_period_end}\n";
            }
            
            // Debug: Check billing period dates
            echo "Looking for invoices with period_start={$billingPeriod->getStartDate()} and period_end={$billingPeriod->getEndDate()}\n";
            
            // Verify results
            $this->assertTrue($result->isSuccessful(), 
                "Billing cycle should succeed for iteration {$iteration}");
            
            // The billing engine processes all tenants in the system, including both owner and renter tenants
            $totalTenantsInSystem = $tenantCount + $totalRenterTenants;
            $this->assertEquals($totalTenantsInSystem, $result->getProcessedTenants(),
                "Should process all {$totalTenantsInSystem} tenants (owners + renters) in iteration {$iteration}");
            
            // Verify invoices were created for properties with active configurations
            $generatedInvoices = Invoice::where('billing_period_start', $billingPeriod->getStartDate())
                ->where('billing_period_end', $billingPeriod->getEndDate())
                ->count();
            
            echo "Found {$generatedInvoices} invoices matching billing period dates\n";
            
            $this->assertGreaterThanOrEqual($totalProperties, $generatedInvoices,
                "Should generate at least one invoice per property in iteration {$iteration}");
            
            $this->assertLessThanOrEqual(100, $result->getSuccessRate(),
                "Success rate should not exceed 100% in iteration {$iteration}");
        }
    }

    /**
     * Property: Billing cycle handles errors gracefully without corrupting data.
     * 
     * @test
     */
    public function billing_cycle_handles_errors_gracefully(): void
    {
        // Run multiple scenarios with error conditions
        for ($iteration = 1; $iteration <= 30; $iteration++) {
            // Clean up data from previous iteration
            Invoice::truncate();
            MeterReading::truncate();
            Meter::truncate();
            ServiceConfiguration::truncate();
            Property::truncate();
            Tenant::truncate();
            
            // Create test data with potential error conditions
            $ownerTenant = Tenant::factory()->create();
            $properties = Property::factory()->count(rand(2, 5))->create([
                'tenant_id' => $ownerTenant->tenant_id,
            ]);
            
            $renterTenantCount = 0;
            foreach ($properties as $index => $property) {
                // Ensure property has a tenant (renter)
                $renterTenant = Tenant::factory()->create([
                    'tenant_id' => $property->tenant_id,
                    'property_id' => $property->id,
                ]);
                $renterTenantCount++;
                
                // Create some properties with missing data to trigger errors
                if ($index % 2 === 0) {
                    // Property with no meters (should generate warning)
                    continue;
                }
                
                // Property with meters but no readings (should generate warning)
                Meter::factory()
                    ->create([
                        'property_id' => $property->id,
                        'type' => MeterType::ELECTRICITY,
                    ]);
                
                if ($index % 3 === 0) {
                    // Add service configuration for some properties
                    $utilityService = UtilityService::factory()->create();
                    ServiceConfiguration::factory()
                        ->create([
                            'property_id' => $property->id,
                            'utility_service_id' => $utilityService->id,
                            'is_active' => true,
                        ]);
                }
            }
            
            // Execute billing cycle
            $billingPeriod = BillingPeriod::currentMonth();
            $options = BillingOptions::fromArray(['create_zero_invoices' => false]);
            $result = $this->billingEngine->processBillingCycle($billingPeriod, $options);
            
            // Verify error handling
            $totalTenantsInSystem = 1 + $renterTenantCount; // owner + renters
            $this->assertEquals($totalTenantsInSystem, $result->getProcessedTenants(),
                "Should process all {$totalTenantsInSystem} tenants even with errors in iteration {$iteration}");
            
            // Should have warnings for properties without meters/readings
            $this->assertTrue($result->hasWarnings() || $result->getGeneratedInvoices() === 0,
                "Should have warnings or no invoices for problematic data in iteration {$iteration}");
            
            // Verify no partial data corruption
            $allInvoices = Invoice::all();
            foreach ($allInvoices as $invoice) {
                $this->assertNotNull($invoice->tenant_renter_id,
                    "Invoice should have valid tenant_renter_id in iteration {$iteration}");
                $this->assertNotNull($invoice->tenant_id,
                    "Invoice should have valid tenant_id in iteration {$iteration}");
                $this->assertGreaterThanOrEqual(0, $invoice->total_amount,
                    "Invoice amount should be non-negative in iteration {$iteration}");
            }
        }
    }

    /**
     * Property: Different billing schedules produce consistent results
     * for the same billing period and data.
     * 
     * @test
     */
    public function different_schedules_produce_consistent_results(): void
    {
        // Run multiple scenarios with different schedules
        for ($iteration = 1; $iteration <= 25; $iteration++) {
            // Clean up data from previous iteration
            Invoice::truncate();
            MeterReading::truncate();
            Meter::truncate();
            ServiceConfiguration::truncate();
            Property::truncate();
            Tenant::truncate();
            
            // Create consistent test data
            $ownerTenant = Tenant::factory()->create();
            $property = Property::factory()->create([
                'tenant_id' => $ownerTenant->tenant_id,
            ]);
            
            // Ensure property has a tenant (renter)
            $renterTenant = Tenant::factory()->create([
                'tenant_id' => $property->tenant_id,
                'property_id' => $property->id,
            ]);
            
            $meter = Meter::factory()
                ->create([
                    'property_id' => $property->id,
                    'type' => MeterType::ELECTRICITY,
                ]);
            
            $utilityService = UtilityService::factory()->create([
                'service_type_bridge' => ServiceType::ELECTRICITY,
                'default_pricing_model' => PricingModel::FLAT,
            ]);
            
            ServiceConfiguration::factory()
                ->create([
                    'property_id' => $property->id,
                    'utility_service_id' => $utilityService->id,
                    'is_active' => true,
                    'rate_schedule' => ['base_rate' => 0.15],
                ]);
            
            MeterReading::factory()
                ->create([
                    'meter_id' => $meter->id,
                    'reading_date' => now()->subDays(15),
                    'value' => 1000,
                ]);
            
            $billingPeriod = BillingPeriod::currentMonth();
            
            // Test monthly schedule
            $options = BillingOptions::default();
            $monthlyResult = $this->billingEngine->processBillingCycle($billingPeriod, $options);
            
            // Reset and test custom schedule with same parameters
            Invoice::truncate();
            
            $customOptions = BillingOptions::fromArray(['tenant_ids' => [$ownerTenant->id, $renterTenant->id]]);
            $customResult = $this->billingEngine->processBillingCycle($billingPeriod, $customOptions);
            
            // Verify consistency
            $this->assertEquals($monthlyResult->getProcessedTenants(), 
                $customResult->getProcessedTenants(),
                "Both schedules should process same number of tenants in iteration {$iteration}");
            
            $this->assertEquals($monthlyResult->getGeneratedInvoices(),
                $customResult->getGeneratedInvoices(),
                "Both schedules should generate same number of invoices in iteration {$iteration}");
            
            // Allow for small floating point differences
            $this->assertEqualsWithDelta($monthlyResult->getTotalAmount(),
                $customResult->getTotalAmount(), 0.01,
                "Both schedules should generate same total amount in iteration {$iteration}");
        }
    }

    /**
     * Property: Billing cycle respects existing invoices and doesn't duplicate
     * unless explicitly requested.
     * 
     * @test
     */
    public function billing_cycle_respects_existing_invoices(): void
    {
        // Run multiple scenarios with existing invoices
        for ($iteration = 1; $iteration <= 40; $iteration++) {
            // Clean up data from previous iteration
            Invoice::truncate();
            MeterReading::truncate();
            Meter::truncate();
            ServiceConfiguration::truncate();
            Property::truncate();
            Tenant::truncate();
            
            // Create test data
            $tenant = Tenant::factory()->create();
            $property = Property::factory()->create([
                'tenant_id' => $tenant->tenant_id,
            ]);
            
            $meter = Meter::factory()
                ->create([
                    'property_id' => $property->id,
                    'type' => MeterType::WATER_COLD,
                ]);
            
            $utilityService = UtilityService::factory()->create([
                'service_type_bridge' => ServiceType::WATER,
            ]);
            
            ServiceConfiguration::factory()
                ->create([
                    'property_id' => $property->id,
                    'utility_service_id' => $utilityService->id,
                    'is_active' => true,
                ]);
            
            MeterReading::factory()
                ->create([
                    'meter_id' => $meter->id,
                    'reading_date' => now()->subDays(10),
                    'value' => rand(500, 1500),
                ]);
            
            $billingPeriod = BillingPeriod::currentMonth();
            
            // Create existing invoice using tenant relationship
            $tenant = $property->tenant;
            if (!$tenant) {
                // Create a tenant for this property if it doesn't exist
                $tenant = Tenant::factory()->create([
                    'tenant_id' => $property->tenant_id,
                    'property_id' => $property->id,
                ]);
            }
            
            $existingInvoice = Invoice::factory()
                ->create([
                    'tenant_renter_id' => $tenant->id,
                    'tenant_id' => $property->tenant_id,
                    'billing_period_start' => $billingPeriod->getStartDate(),
                    'billing_period_end' => $billingPeriod->getEndDate(),
                    'status' => InvoiceStatus::DRAFT,
                    'total_amount' => rand(50, 200),
                ]);
            
            // Execute billing cycle without regeneration
            $options1 = BillingOptions::fromArray(['regenerate_existing' => false]);
            $result1 = $this->billingEngine->processBillingCycle($billingPeriod, $options1);
            
            // Should not create duplicate invoice
            $invoiceCount1 = Invoice::where('tenant_renter_id', $tenant->id)
                ->where('billing_period_start', $billingPeriod->getStartDate())
                ->count();
            
            $this->assertEquals(1, $invoiceCount1,
                "Should not duplicate existing invoice in iteration {$iteration}");
            
            $this->assertTrue($result1->hasWarnings(),
                "Should have warnings about existing invoice in iteration {$iteration}");
            
            // Execute billing cycle with regeneration
            $options2 = BillingOptions::fromArray(['regenerate_existing' => true]);
            $result2 = $this->billingEngine->processBillingCycle($billingPeriod, $options2);
            
            // Should still have only one invoice (replaced)
            $invoiceCount2 = Invoice::where('tenant_renter_id', $tenant->id)
                ->where('billing_period_start', $billingPeriod->getStartDate())
                ->count();
            
            $this->assertEquals(1, $invoiceCount2,
                "Should replace existing invoice when regeneration enabled in iteration {$iteration}");
            
            $this->assertEquals(1, $result2->getGeneratedInvoices(),
                "Should report one generated invoice in iteration {$iteration}");
        }
    }

    /**
     * Property: Billing amounts are always non-negative and within reasonable bounds.
     * 
     * @test
     */
    public function billing_amounts_are_within_reasonable_bounds(): void
    {
        // Run multiple scenarios with various configurations
        for ($iteration = 1; $iteration <= 35; $iteration++) {
            // Clean up data from previous iteration
            Invoice::truncate();
            MeterReading::truncate();
            Meter::truncate();
            ServiceConfiguration::truncate();
            Property::truncate();
            Tenant::truncate();
            UtilityService::truncate();
            
            // Create test data with random but realistic values
            $tenant = Tenant::factory()->create();
            $property = Property::factory()->create([
                'tenant_id' => $tenant->tenant_id,
            ]);
            
            // Ensure property has a tenant (renter)
            $renterTenant = Tenant::factory()->create([
                'tenant_id' => $property->tenant_id,
                'property_id' => $property->id,
            ]);
            
            $meterCount = rand(1, 4);
            $totalExpectedAmount = 0.0;
            
            for ($i = 0; $i < $meterCount; $i++) {
                $meter = Meter::factory()
                    ->create([
                        'property_id' => $property->id,
                        'type' => $this->faker->randomElement(MeterType::cases()),
                    ]);
                
                $utilityService = UtilityService::factory()->create([
                    'default_pricing_model' => $this->faker->randomElement(PricingModel::cases()),
                ]);
                
                $baseRate = rand(10, 50) / 100; // 0.10 to 0.50
                ServiceConfiguration::factory()
                    ->create([
                        'property_id' => $property->id,
                        'utility_service_id' => $utilityService->id,
                        'is_active' => true,
                        'rate_schedule' => ['base_rate' => $baseRate],
                    ]);
                
                $consumption = rand(50, 500);
                MeterReading::factory()
                    ->create([
                        'meter_id' => $meter->id,
                        'reading_date' => now()->subDays(rand(1, 30)),
                        'value' => $consumption,
                    ]);
                
                // Rough estimate for validation
                $totalExpectedAmount += $consumption * $baseRate;
            }
            
            // Execute billing cycle
            $billingPeriod = BillingPeriod::currentMonth();
            $options = BillingOptions::default();
            $result = $this->billingEngine->processBillingCycle($billingPeriod, $options);
            
            // Verify amount bounds
            $this->assertGreaterThanOrEqual(0, $result->getTotalAmount(),
                "Total amount should be non-negative in iteration {$iteration}");
            
            // Should be within reasonable bounds (allowing for calculation differences)
            $this->assertLessThan($totalExpectedAmount * 2, $result->getTotalAmount(),
                "Total amount should be within reasonable bounds in iteration {$iteration}");
            
            // Verify individual invoice amounts
            $invoices = Invoice::where('billing_period_start', $billingPeriod->getStartDate())->get();
            foreach ($invoices as $invoice) {
                $this->assertGreaterThanOrEqual(0, $invoice->total_amount,
                    "Invoice amount should be non-negative in iteration {$iteration}");
                
                $this->assertLessThan(10000, $invoice->total_amount,
                    "Invoice amount should be reasonable in iteration {$iteration}");
            }
        }
    }

    /**
     * Property: Billing cycle execution is idempotent when run multiple times
     * with the same parameters (without regeneration).
     * 
     * @test
     */
    public function billing_cycle_execution_is_idempotent(): void
    {
        // Run multiple scenarios to test idempotency
        for ($iteration = 1; $iteration <= 20; $iteration++) {
            // Clean up data from previous iteration
            Invoice::truncate();
            MeterReading::truncate();
            Meter::truncate();
            ServiceConfiguration::truncate();
            Property::truncate();
            Tenant::truncate();
            UtilityService::truncate();
            
            // Create consistent test data
            $tenant = Tenant::factory()->create();
            $property = Property::factory()->create([
                'tenant_id' => $tenant->tenant_id,
            ]);
            
            // Ensure property has a tenant (renter)
            $renterTenant = Tenant::factory()->create([
                'tenant_id' => $property->tenant_id,
                'property_id' => $property->id,
            ]);
            
            $meter = Meter::factory()
                ->create([
                    'property_id' => $property->id,
                    'type' => MeterType::HEATING,
                ]);
            
            $utilityService = UtilityService::factory()->create();
            ServiceConfiguration::factory()
                ->create([
                    'property_id' => $property->id,
                    'utility_service_id' => $utilityService->id,
                    'is_active' => true,
                ]);
            
            MeterReading::factory()
                ->create([
                    'meter_id' => $meter->id,
                    'reading_date' => now()->subDays(5),
                    'value' => 750,
                ]);
            
            $billingPeriod = BillingPeriod::currentMonth();
            
            // Execute billing cycle first time
            $options1 = BillingOptions::fromArray(['regenerate_existing' => false]);
            $result1 = $this->billingEngine->processBillingCycle($billingPeriod, $options1);
            
            // Execute billing cycle second time with same parameters
            $options2 = BillingOptions::fromArray(['regenerate_existing' => false]);
            $result2 = $this->billingEngine->processBillingCycle($billingPeriod, $options2);
            
            // Verify idempotency
            $this->assertEquals($result1->getProcessedTenants(), $result2->getProcessedTenants(),
                "Should process same number of tenants on repeat execution in iteration {$iteration}");
            
            // Second execution should have warnings about existing invoices
            $this->assertTrue($result2->hasWarnings(),
                "Second execution should have warnings about existing invoices in iteration {$iteration}");
            
            // Should not create duplicate invoices
            $invoiceCount = Invoice::where('tenant_renter_id', $renterTenant->id)
                ->where('billing_period_start', $billingPeriod->getStartDate())
                ->count();
            
            $this->assertEquals(1, $invoiceCount,
                "Should not create duplicate invoices on repeat execution in iteration {$iteration}");
            
            // Total amount should remain the same
            $this->assertEquals($result1->getTotalAmount(), $result2->getTotalAmount(),
                "Total amount should remain same on repeat execution in iteration {$iteration}");
        }
    }

    /**
     * Property: Billing cycle maintains audit trail and logging for all operations.
     * 
     * @test
     */
    public function billing_cycle_maintains_audit_trail(): void
    {
        // Run multiple scenarios to test audit trail
        for ($iteration = 1; $iteration <= 15; $iteration++) {
            // Clean up data from previous iteration
            Invoice::truncate();
            MeterReading::truncate();
            Meter::truncate();
            ServiceConfiguration::truncate();
            Property::truncate();
            Tenant::truncate();
            UtilityService::truncate();
            
            // Create test data
            $tenant = Tenant::factory()->create();
            $property = Property::factory()->create([
                'tenant_id' => $tenant->tenant_id,
            ]);
            
            // Ensure property has a tenant (renter)
            $renterTenant = Tenant::factory()->create([
                'tenant_id' => $property->tenant_id,
                'property_id' => $property->id,
            ]);
            
            $meter = Meter::factory()
                ->create([
                    'property_id' => $property->id,
                    'type' => MeterType::ELECTRICITY,
                ]);
            
            $utilityService = UtilityService::factory()->create();
            ServiceConfiguration::factory()
                ->create([
                    'property_id' => $property->id,
                    'utility_service_id' => $utilityService->id,
                    'is_active' => true,
                ]);
            
            MeterReading::factory()
                ->create([
                    'meter_id' => $meter->id,
                    'reading_date' => now()->subDays(rand(1, 20)),
                    'value' => rand(200, 800),
                ]);
            
            // Execute billing cycle
            $billingPeriod = BillingPeriod::currentMonth();
            $options = BillingOptions::default();
            $result = $this->billingEngine->processBillingCycle($billingPeriod, $options);
            
            // Verify audit trail in generated invoices
            $invoices = Invoice::where('billing_period_start', $billingPeriod->getStartDate())->get();
            
            foreach ($invoices as $invoice) {
                $this->assertNotNull($invoice->generated_at,
                    "Invoice should have generation timestamp in iteration {$iteration}");
                
                $this->assertEquals('automated_billing_engine', $invoice->generated_by,
                    "Invoice should record generation source in iteration {$iteration}");
                
                $this->assertNotNull($invoice->items,
                    "Invoice should have itemized details in iteration {$iteration}");
                
                if (is_array($invoice->items)) {
                    foreach ($invoice->items as $item) {
                        $this->assertArrayHasKey('description', $item,
                            "Invoice item should have description in iteration {$iteration}");
                        $this->assertArrayHasKey('amount', $item,
                            "Invoice item should have amount in iteration {$iteration}");
                    }
                }
            }
            
            // Verify result contains audit information
            $summary = $result->getSummary();
            $this->assertArrayHasKey('processed_tenants', $summary,
                "Result should contain processing summary in iteration {$iteration}");
            $this->assertArrayHasKey('generated_invoices', $summary,
                "Result should contain invoice count in iteration {$iteration}");
            $this->assertArrayHasKey('success_rate', $summary,
                "Result should contain success rate in iteration {$iteration}");
        }
    }
}