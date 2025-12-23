<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SharedServiceCostDistributor;
use App\Enums\BillingSchedule;
use App\Enums\InvoiceStatus;
use App\Exceptions\BillingException;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Models\Tenant;
use App\Services\Billing\PropertyBillingProcessor;
use App\Services\Billing\TenantBillingProcessor;
use App\Services\HeatingCalculatorService;
use App\Services\SharedServiceCostDistributorService;
use App\Services\UniversalReadingCollector;
use App\ValueObjects\AutomatedBillingResult;
use App\ValueObjects\BillingOptions;
use App\ValueObjects\BillingPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Automated billing engine orchestrator.
 * 
 * Coordinates the automated billing cycle execution by delegating
 * to specialized processors for different aspects of billing.
 * Follows the Single Responsibility Principle by focusing on
 * orchestration rather than implementation details.
 * 
 * @package App\Services
 * @see \App\Services\Billing\TenantBillingProcessor
 * @see \App\ValueObjects\AutomatedBillingResult
 * @see \App\ValueObjects\BillingOptions
 * 
 * @example
 * ```php
 * $billingEngine = app(AutomatedBillingEngine::class);
 * $results = $billingEngine->executeBillingCycle(
 *     BillingPeriod::currentMonth(),
 *     BillingSchedule::MONTHLY,
 *     BillingOptions::default()
 * );
 * ```
 */
final readonly class AutomatedBillingEngine
{
    public function __construct(
        private PropertyBillingProcessor $propertyProcessor,
        private TenantBillingProcessor $tenantProcessor,
        private UniversalReadingCollector $readingCollector,
        private SharedServiceCostDistributorService $costDistributor,
        private HeatingCalculatorService $heatingCalculator,
        private InvoiceSnapshotService $snapshotService
    ) {}

    /**
     * Process automated billing cycle with enhanced transaction handling.
     * 
     * @param BillingPeriod $period The billing period to process
     * @param BillingOptions $options Configuration options for billing execution
     * @return AutomatedBillingResult Results of the billing cycle execution
     * 
     * @throws BillingException If billing cycle fails
     */
    public function processBillingCycle(BillingPeriod $period, BillingOptions $options): AutomatedBillingResult
    {
        Log::info('Starting automated billing cycle', [
            'period' => $period->toArray(),
            'options' => $options->toArray()
        ]);

        // Check if we're in a test environment or already in a transaction
        $inTransaction = DB::transactionLevel() > 0;
        $isTestEnvironment = app()->environment('testing') || config('app.env') === 'testing';
        
        if ($inTransaction || $isTestEnvironment) {
            // Already in transaction or in test environment, proceed without wrapping
            Log::debug('Skipping transaction wrapper', [
                'transaction_level' => DB::transactionLevel(),
                'is_testing' => $isTestEnvironment
            ]);
            return $this->executeBillingCycle($period, BillingSchedule::MONTHLY, $options);
        }

        // Not in transaction and not in test environment, wrap in transaction for data consistency
        Log::debug('Using transaction wrapper', [
            'transaction_level' => DB::transactionLevel()
        ]);
        
        return DB::transaction(function () use ($period, $options) {
            return $this->executeBillingCycle($period, BillingSchedule::MONTHLY, $options);
        });
    }

    /**
     * Execute automated billing cycle for specified period and schedule.
     * 
     * @param BillingPeriod $billingPeriod The billing period to process
     * @param BillingSchedule|string $schedule Billing schedule type
     * @param BillingOptions|array|null $options Configuration options for billing execution
     * @return AutomatedBillingResult Results of the billing cycle execution
     * 
     * @throws BillingException If billing cycle fails
     */
    public function executeBillingCycle(
        BillingPeriod $billingPeriod,
        BillingSchedule|string $schedule,
        BillingOptions|array|null $options = null
    ): AutomatedBillingResult {
        // Normalize schedule to enum
        if (is_string($schedule)) {
            $schedule = BillingSchedule::fromString($schedule);
        }
        
        // Normalize options to value object
        if (is_array($options)) {
            $options = BillingOptions::fromArray($options);
        }
        $options ??= BillingOptions::default();
        
        Log::info('Starting automated billing cycle', [
            'period' => $billingPeriod->getLabel(),
            'schedule' => $schedule->value,
            'options' => $options->toArray(),
        ]);

        try {
            // Execute billing logic without transaction wrapper
            // (transaction handling should be done by caller if needed)
            $results = $this->initializeResults();

            // Step 1: Collect readings if enabled
            if ($options->shouldAutoCollectReadings()) {
                $results['reading_collection_results'] = $this->collectReadingsForPeriod($billingPeriod, $options);
            }

            // Step 2: Process tenants
            $tenants = $this->getTenantsForBilling($schedule, $options);
            $tenantResults = $this->processAllTenants($tenants, $billingPeriod, $options);
            
            $results = array_merge($results, $tenantResults);

            // Step 3: Handle shared services
            if ($options->shouldProcessSharedServices()) {
                $results['shared_service_results'] = $this->processSharedServiceCosts($billingPeriod, $options);
            }

            $billingResult = AutomatedBillingResult::fromArray($results);
            
            Log::info('Automated billing cycle completed', $billingResult->toArray());
            
            return $billingResult;

        } catch (\Exception $e) {
            Log::error('Automated billing cycle failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'period' => $billingPeriod->getLabel(),
                'schedule' => $schedule->value,
            ]);
            
            throw BillingException::from($e);
        }
    }

    /**
     * Initialize results array with default values.
     */
    private function initializeResults(): array
    {
        return [
            'processed_tenants' => 0,
            'generated_invoices' => 0,
            'total_amount' => 0.0,
            'errors' => [],
            'warnings' => [],
            'reading_collection_results' => [],
            'shared_service_results' => [],
            'metadata' => [
                'started_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Process all tenants for billing.
     */
    private function processAllTenants(
        Collection $tenants,
        BillingPeriod $billingPeriod,
        BillingOptions $options
    ): array {
        $results = [
            'processed_tenants' => 0,
            'generated_invoices' => 0,
            'total_amount' => 0.0,
            'errors' => [],
            'warnings' => [],
        ];

        foreach ($tenants as $tenant) {
            try {
                $tenantResult = $this->tenantProcessor->processTenant(
                    $tenant,
                    $billingPeriod,
                    $options
                );
                
                $results['processed_tenants']++;
                $results['generated_invoices'] += $tenantResult['invoices_generated'];
                $results['total_amount'] += $tenantResult['total_amount'];
                
                if (!empty($tenantResult['warnings'])) {
                    $results['warnings'] = array_merge($results['warnings'], $tenantResult['warnings']);
                }
                
            } catch (\Exception $e) {
                $error = "Tenant {$tenant->id} processing failed: {$e->getMessage()}";
                $results['errors'][] = $error;
                
                Log::error('Tenant processing failed', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Collect readings for the billing period using automated methods.
     */
    private function collectReadingsForPeriod(BillingPeriod $billingPeriod, BillingOptions $options): array
    {
        $results = [
            'collected_readings' => 0,
            'failed_collections' => 0,
            'errors' => [],
        ];

        try {
            $collectionResult = $this->readingCollector->collectReadingsForPeriod($billingPeriod, $options);
            
            $results['collected_readings'] = $collectionResult->getSuccessCount();
            $results['failed_collections'] = $collectionResult->getFailureCount();
            $results['errors'] = $collectionResult->getErrors();
            
        } catch (\Exception $e) {
            $results['errors'][] = "Reading collection failed: {$e->getMessage()}";
        }

        return $results;
    }

    /**
     * Process shared service cost distribution.
     */
    private function processSharedServiceCosts(BillingPeriod $billingPeriod, BillingOptions $options): array
    {
        $results = [
            'processed_services' => 0,
            'total_distributed' => 0.0,
            'errors' => [],
        ];

        try {
            // Get shared service configurations
            $sharedServices = ServiceConfiguration::where('is_shared_service', true)->get();

            foreach ($sharedServices as $serviceConfig) {
                try {
                    // Get properties that use this shared service
                    $properties = Property::whereHas('serviceConfigurations', function ($query) use ($serviceConfig) {
                        $query->where('id', $serviceConfig->id);
                    })->get();

                    if ($properties->isEmpty()) {
                        continue;
                    }

                    // Calculate total cost for the period
                    $totalCost = $this->calculateSharedServiceCost($serviceConfig, $billingPeriod);

                    if ($totalCost <= 0) {
                        continue;
                    }

                    // Distribute cost among properties
                    $distributionResult = $this->costDistributor->distributeCost(
                        $serviceConfig,
                        $properties,
                        $totalCost,
                        $billingPeriod
                    );

                    // Apply distributed costs to property invoices
                    $this->applyDistributedCosts($distributionResult, $billingPeriod, $serviceConfig);

                    $results['processed_services']++;
                    $results['total_distributed'] += $totalCost;

                } catch (\Exception $e) {
                    $results['errors'][] = "Shared service {$serviceConfig->id} failed: {$e->getMessage()}";
                }
            }
        } catch (\Exception $e) {
            $results['errors'][] = "Shared service processing failed: {$e->getMessage()}";
        }

        return $results;
    }

    /**
     * Calculate total cost for a shared service in the billing period.
     */
    private function calculateSharedServiceCost(
        ServiceConfiguration $serviceConfig,
        BillingPeriod $billingPeriod
    ): float {
        // This would typically involve complex calculations based on
        // actual usage, provider costs, maintenance costs, etc.
        // For now, return a simple calculation based on configuration
        
        $baseCost = $serviceConfig->rate_schedule['base_cost'] ?? 0.0;
        $variableCost = $serviceConfig->rate_schedule['variable_cost'] ?? 0.0;
        
        // Add seasonal adjustments if applicable
        $seasonalMultiplier = $this->getSeasonalMultiplier($billingPeriod->getStartDate());
        
        return ($baseCost + $variableCost) * $seasonalMultiplier;
    }

    /**
     * Apply distributed costs to property invoices.
     */
    private function applyDistributedCosts(
        $distributionResult,
        BillingPeriod $billingPeriod,
        ServiceConfiguration $serviceConfig
    ): void {
        foreach ($distributionResult->getDistributions() as $propertyId => $amount) {
            if ($amount <= 0) {
                continue;
            }

            // Find or create invoice for this property and period
            $invoice = Invoice::firstOrCreate([
                'property_id' => $propertyId,
                'billing_period_start' => $billingPeriod->getStartDate(),
                'billing_period_end' => $billingPeriod->getEndDate(),
            ], [
                'status' => InvoiceStatus::DRAFT,
                'total_amount' => 0.0,
                'items' => [],
                'generated_at' => now(),
                'generated_by' => 'automated_billing_engine',
            ]);

            // Add shared service cost as invoice item
            $items = $invoice->items ?? [];
            $items[] = [
                'service_configuration_id' => $serviceConfig->id,
                'service_type' => 'shared_service',
                'description' => "Shared {$serviceConfig->utilityService->name} for {$billingPeriod->getLabel()}",
                'amount' => $amount,
                'distribution_details' => $distributionResult->getMetadata(),
            ];

            $invoice->items = $items;
            $invoice->total_amount += $amount;
            $invoice->save();
        }
    }

    /**
     * Get tenants that should be processed for the given schedule.
     */
    private function getTenantsForBilling(BillingSchedule $schedule, BillingOptions $options): Collection
    {
        $query = Tenant::query();

        // Filter by specific tenant IDs if provided
        if ($tenantIds = $options->getTenantIds()) {
            $query->whereIn('id', $tenantIds);
        }

        // Add other filters based on schedule
        switch ($schedule) {
            case BillingSchedule::MONTHLY:
                // All active tenants for monthly billing
                break;
            case BillingSchedule::QUARTERLY:
                // Filter tenants that use quarterly billing
                $query->where('billing_schedule', 'quarterly');
                break;
            case BillingSchedule::CUSTOM:
                // Custom filtering based on options
                if ($customFilter = $options->getCustomFilter()) {
                    foreach ($customFilter as $field => $value) {
                        $query->where($field, $value);
                    }
                }
                break;
        }

        return $query->get();
    }

    /**
     * Get seasonal multiplier for cost adjustments.
     */
    private function getSeasonalMultiplier(Carbon $date): float
    {
        // Use existing heating calculator logic for seasonal adjustments
        return $this->heatingCalculator->getSeasonalMultiplier($date);
    }

    /**
     * Get supported billing schedules.
     */
    public function getSupportedSchedules(): array
    {
        return BillingSchedule::values();
    }
}