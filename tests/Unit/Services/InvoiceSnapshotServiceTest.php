<?php

declare(strict_types=1);

use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\Property;
use App\Models\Tariff;
use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use App\Services\InvoiceSnapshotService;
use App\ValueObjects\BillingPeriod;
use App\ValueObjects\BillingOptions;
use App\Enums\InvoiceStatus;
use App\Enums\ServiceType;
use App\Enums\PricingModel;
use App\Enums\DistributionMethod;
use Carbon\Carbon;

beforeEach(function () {
    $this->snapshotService = app(InvoiceSnapshotService::class);
    
    // Create test data
    $this->tenant = Tenant::factory()->create();
    $this->property = Property::factory()->create();
    
    $this->invoice = Invoice::factory()->create([
        'tenant_renter_id' => $this->tenant->id,
        'property_id' => $this->property->id,
        'status' => InvoiceStatus::DRAFT,
        'total_amount' => 150.00,
    ]);
    
    $this->billingPeriod = BillingPeriod::create(
        Carbon::now()->startOfMonth(),
        Carbon::now()->endOfMonth()
    );
    
    $this->billingOptions = BillingOptions::default();
});

it('creates complete invoice snapshot with all required data', function () {
    // Create test tariff
    $tariff = Tariff::factory()->create([
        'name' => 'Test Electricity Tariff',
        'rates' => ['day' => 0.15, 'night' => 0.10],
        'active_from' => $this->billingPeriod->getStartDate()->subDay(),
        'active_until' => null,
    ]);
    
    // Create test utility service
    $utilityService = UtilityService::factory()->create([
        'name' => 'Electricity Service',
        'service_type_bridge' => ServiceType::ELECTRICITY,
        'unit_of_measurement' => 'kWh',
        'default_pricing_model' => PricingModel::TIME_OF_USE,
    ]);
    
    // Create test service configuration
    $serviceConfig = ServiceConfiguration::factory()->create([
        'property_id' => $this->property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => PricingModel::TIME_OF_USE,
        'distribution_method' => DistributionMethod::EQUAL,
        'effective_from' => $this->billingPeriod->getStartDate()->subDay(),
        'is_active' => true,
    ]);
    
    // Create snapshot
    $snapshot = $this->snapshotService->createInvoiceSnapshot(
        $this->invoice,
        $this->billingPeriod,
        $this->billingOptions
    );
    
    // Verify snapshot structure
    expect($snapshot)->toHaveKeys([
        'created_at',
        'billing_period',
        'billing_options',
        'tariff_snapshots',
        'service_configuration_snapshots',
        'utility_service_snapshots',
        'calculation_metadata',
    ]);
    
    // Verify tariff snapshot
    expect($snapshot['tariff_snapshots'])->toHaveCount(1);
    expect($snapshot['tariff_snapshots'][$tariff->id])->toMatchArray([
        'id' => $tariff->id,
        'name' => 'Test Electricity Tariff',
        'type' => $tariff->type->value,
        'rates' => ['day' => 0.15, 'night' => 0.10],
    ]);
    
    // Verify service configuration snapshot
    expect($snapshot['service_configuration_snapshots'])->toHaveCount(1);
    expect($snapshot['service_configuration_snapshots'][$serviceConfig->id])->toMatchArray([
        'id' => $serviceConfig->id,
        'property_id' => $this->property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => PricingModel::TIME_OF_USE->value,
        'distribution_method' => DistributionMethod::EQUAL->value,
    ]);
    
    // Verify utility service snapshot
    expect($snapshot['utility_service_snapshots'])->toHaveCount(1);
    expect($snapshot['utility_service_snapshots'][$utilityService->id])->toMatchArray([
        'id' => $utilityService->id,
        'name' => 'Electricity Service',
        'service_type_bridge' => ServiceType::ELECTRICITY->value,
        'unit_of_measurement' => 'kWh',
        'default_pricing_model' => PricingModel::TIME_OF_USE->value,
    ]);
    
    // Verify calculation metadata
    expect($snapshot['calculation_metadata'])->toHaveKeys([
        'calculation_engine_version',
        'calculation_timestamp',
        'billing_period_days',
        'seasonal_adjustments_applied',
        'heating_calculations_included',
        'shared_service_distributions_applied',
        'automated_reading_collection_used',
        'approval_workflow_required',
        'calculation_complexity_score',
    ]);
    
    // Verify invoice was updated with snapshot
    $this->invoice->refresh();
    expect($this->invoice->hasSnapshot())->toBeTrue();
    expect($this->invoice->snapshot_created_at)->not->toBeNull();
});

it('stores snapshot data in invoice model', function () {
    $snapshot = $this->snapshotService->createInvoiceSnapshot(
        $this->invoice,
        $this->billingPeriod,
        $this->billingOptions
    );
    
    $this->invoice->refresh();
    
    expect($this->invoice->snapshot_data)->toBe($snapshot);
    expect($this->invoice->snapshot_created_at)->not->toBeNull();
    expect($this->invoice->hasSnapshot())->toBeTrue();
});

it('can restore calculation context from snapshot', function () {
    // Create snapshot first
    $this->snapshotService->createInvoiceSnapshot(
        $this->invoice,
        $this->billingPeriod,
        $this->billingOptions
    );
    
    $this->invoice->refresh();
    
    // Restore context
    $context = $this->snapshotService->restoreCalculationContext($this->invoice);
    
    expect($context)->toHaveKeys([
        'tariffs',
        'service_configurations',
        'utility_services',
        'billing_period',
        'billing_options',
        'calculation_metadata',
    ]);
    
    expect($context['billing_period'])->toBe($this->invoice->snapshot_data['billing_period']);
    expect($context['billing_options'])->toBe($this->invoice->snapshot_data['billing_options']);
});

it('validates recalculation capability from snapshot', function () {
    // Invoice without snapshot
    expect($this->snapshotService->canRecalculateFromSnapshot($this->invoice))->toBeFalse();
    
    // Create snapshot
    $this->snapshotService->createInvoiceSnapshot(
        $this->invoice,
        $this->billingPeriod,
        $this->billingOptions
    );
    
    $this->invoice->refresh();
    
    // Invoice with complete snapshot
    expect($this->snapshotService->canRecalculateFromSnapshot($this->invoice))->toBeTrue();
});

it('generates snapshot summary with correct metadata', function () {
    // Create test data for more complex scenario
    $utilityService = UtilityService::factory()->create([
        'service_type_bridge' => ServiceType::HEATING,
    ]);
    
    ServiceConfiguration::factory()->create([
        'property_id' => $this->property->id,
        'utility_service_id' => $utilityService->id,
        'is_shared_service' => true,
        'is_active' => true,
    ]);
    
    // Create snapshot
    $this->snapshotService->createInvoiceSnapshot(
        $this->invoice,
        $this->billingPeriod,
        $this->billingOptions
    );
    
    $this->invoice->refresh();
    
    // Get summary
    $summary = $this->snapshotService->getSnapshotSummary($this->invoice);
    
    expect($summary)->toMatchArray([
        'has_snapshot' => true,
        'has_heating_calculations' => true,
        'has_shared_services' => true,
        'requires_approval' => false,
    ]);
    
    expect($summary['created_at'])->not->toBeNull();
    expect($summary['calculation_complexity_score'])->toBeGreaterThan(0);
});

it('handles seasonal adjustments detection correctly', function () {
    // Test winter period (heating season)
    $winterPeriod = BillingPeriod::create(
        Carbon::create(2024, 1, 1), // January (winter)
        Carbon::create(2024, 1, 31)
    );
    
    $snapshot = $this->snapshotService->createInvoiceSnapshot(
        $this->invoice,
        $winterPeriod,
        $this->billingOptions
    );
    
    expect($snapshot['calculation_metadata']['seasonal_adjustments_applied'])->toBeTrue();
    
    // Test summer period (non-heating season)
    $summerPeriod = BillingPeriod::create(
        Carbon::create(2024, 7, 1), // July (summer)
        Carbon::create(2024, 7, 31)
    );
    
    $snapshot = $this->snapshotService->createInvoiceSnapshot(
        $this->invoice,
        $summerPeriod,
        $this->billingOptions
    );
    
    expect($snapshot['calculation_metadata']['seasonal_adjustments_applied'])->toBeFalse();
});

it('calculates complexity score based on service configurations', function () {
    // Simple scenario - no services
    $snapshot = $this->snapshotService->createInvoiceSnapshot(
        $this->invoice,
        $this->billingPeriod,
        $this->billingOptions
    );
    
    $simpleScore = $snapshot['calculation_metadata']['calculation_complexity_score'];
    expect($simpleScore)->toBe(1); // Base complexity
    
    // Complex scenario - multiple services
    $utilityService1 = UtilityService::factory()->create();
    $utilityService2 = UtilityService::factory()->create(['service_type_bridge' => ServiceType::HEATING]);
    
    ServiceConfiguration::factory()->create([
        'property_id' => $this->property->id,
        'utility_service_id' => $utilityService1->id,
        'is_active' => true,
        'is_shared_service' => false,
    ]);
    
    ServiceConfiguration::factory()->create([
        'property_id' => $this->property->id,
        'utility_service_id' => $utilityService2->id,
        'is_active' => true,
        'is_shared_service' => true, // Shared service adds more complexity
    ]);
    
    $snapshot = $this->snapshotService->createInvoiceSnapshot(
        $this->invoice,
        $this->billingPeriod,
        $this->billingOptions
    );
    
    $complexScore = $snapshot['calculation_metadata']['calculation_complexity_score'];
    expect($complexScore)->toBeGreaterThan($simpleScore);
    expect($complexScore)->toBe(7); // 1 base + 2 services + 2 for shared + 3 for heating
});

it('throws exception when trying to restore context without snapshot', function () {
    expect(fn () => $this->snapshotService->restoreCalculationContext($this->invoice))
        ->toThrow(InvalidArgumentException::class, 'Invoice ' . $this->invoice->id . ' has no snapshot data');
});

it('handles approval workflow requirements correctly', function () {
    // High-value invoice should require approval
    $highValueInvoice = Invoice::factory()->create([
        'tenant_renter_id' => $this->tenant->id,
        'property_id' => $this->property->id,
        'total_amount' => 1500.00, // Above threshold
    ]);
    
    $snapshot = $this->snapshotService->createInvoiceSnapshot(
        $highValueInvoice,
        $this->billingPeriod,
        $this->billingOptions
    );
    
    expect($snapshot['calculation_metadata']['approval_workflow_required'])->toBeTrue();
    
    // Low-value invoice should not require approval
    $lowValueInvoice = Invoice::factory()->create([
        'tenant_renter_id' => $this->tenant->id,
        'property_id' => $this->property->id,
        'total_amount' => 50.00, // Below threshold
    ]);
    
    $snapshot = $this->snapshotService->createInvoiceSnapshot(
        $lowValueInvoice,
        $this->billingPeriod,
        $this->billingOptions
    );
    
    expect($snapshot['calculation_metadata']['approval_workflow_required'])->toBeFalse();
    
    // Test explicit approval requirement
    $optionsWithApproval = $this->billingOptions->withApprovalWorkflow(true, 100.0);
    
    $snapshot = $this->snapshotService->createInvoiceSnapshot(
        $lowValueInvoice,
        $this->billingPeriod,
        $optionsWithApproval
    );
    
    expect($snapshot['calculation_metadata']['approval_workflow_required'])->toBeTrue();
});