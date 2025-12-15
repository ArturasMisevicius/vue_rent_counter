<?php

declare(strict_types=1);

namespace App\Services\Enhanced;

use App\Actions\GenerateInvoiceAction;
use App\DTOs\InvoiceGenerationDTO;
use App\Enums\InvoiceStatus;
use App\Enums\PricingModel;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\Tenant;
use App\Services\ServiceResponse;
use App\Services\UniversalBillingCalculator;
use App\Services\MeterReadingService;
use App\ValueObjects\BillingPeriod;
use App\ValueObjects\UniversalConsumptionData;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Enhanced Billing Service
 * 
 * Orchestrates billing operations with comprehensive business logic:
 * - Invoice generation with consumption calculations
 * - Bulk billing operations with batch optimization
 * - Payment processing integration
 * - Billing period management
 * - Tariff application and rate calculations
 * 
 * @package App\Services\Enhanced
 */
final class BillingService extends BaseService
{
    public function __construct(
        private readonly GenerateInvoiceAction $generateInvoiceAction,
        private readonly UniversalBillingCalculator $billingCalculator,
        private readonly MeterReadingService $meterReadingService,
        private readonly ConsumptionCalculationService $consumptionService
    ) {
        parent::__construct();
    }

    /**
     * Generate a single invoice for a tenant with comprehensive validation.
     *
     * @param InvoiceGenerationDTO $dto Invoice generation parameters
     * @return ServiceResponse<Invoice>
     */
    public function generateInvoice(InvoiceGenerationDTO $dto): ServiceResponse
    {
        try {
            return $this->withMetrics('generate_invoice', function () use ($dto) {
                return $this->executeInTransaction(function () use ($dto) {
                    // Validate tenant exists and user has access
                    $tenant = Tenant::findOrFail($dto->tenantRenterId);

                    if (auth()->check()) {
                        $this->authorize('create', Invoice::class);
                    }

                    $this->validateTenantOwnership($tenant);

                    // Validate billing period
                    $this->validateBillingPeriod($dto->periodStart, $dto->periodEnd);

                    // Check for existing invoice in period
                    if ($this->hasExistingInvoice($tenant, $dto->periodStart, $dto->periodEnd)) {
                        return $this->error('Invoice already exists for this period');
                    }

                    // Generate base invoice
                    $invoice = $this->generateInvoiceAction->execute($dto);

                    // Calculate and add invoice items
                    $this->addInvoiceItems($invoice, $tenant, $dto);

                    // Calculate totals
                    $this->calculateInvoiceTotals($invoice);

                    $this->log('info', 'Invoice generated successfully', [
                        'invoice_id' => $invoice->id,
                        'tenant_id' => $tenant->id,
                        'period_start' => $dto->periodStart->toDateString(),
                        'period_end' => $dto->periodEnd->toDateString(),
                        'total_amount' => $invoice->total_amount,
                    ]);

                    return $this->success($invoice, 'Invoice generated successfully');
                });
            });
        } catch (\Exception $e) {
            $this->handleException($e, [
                'operation' => 'generate_invoice',
                'tenant_id' => $dto->tenantRenterId,
                'period_start' => $dto->periodStart->toDateString(),
                'period_end' => $dto->periodEnd->toDateString(),
            ]);

            return $this->error('Failed to generate invoice: ' . $e->getMessage());
        }
    }

    /**
     * Generate invoices for multiple tenants with batch optimization.
     *
     * @param Collection<Tenant> $tenants
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @return ServiceResponse<array>
     */
    public function generateBulkInvoices(
        Collection $tenants, 
        Carbon $periodStart, 
        Carbon $periodEnd
    ): ServiceResponse {
        try {
            return $this->withMetrics('generate_bulk_invoices', function () use ($tenants, $periodStart, $periodEnd) {
                // Validate all tenants first
                foreach ($tenants as $tenant) {
                    if (auth()->check()) {
                        $this->authorize('create', Invoice::class);
                    }

                    $this->validateTenantOwnership($tenant);
                }

                $results = [
                    'successful' => [],
                    'failed' => [],
                    'skipped' => [],
                    'total_processed' => 0,
                ];

                // Process in chunks to manage memory
                $tenants->chunk(10)->each(function ($chunk) use ($periodStart, $periodEnd, &$results) {
                    foreach ($chunk as $tenant) {
                        $results['total_processed']++;

                        try {
                            // Check for existing invoice
                            if ($this->hasExistingInvoice($tenant, $periodStart, $periodEnd)) {
                                $results['skipped'][] = [
                                    'tenant_id' => $tenant->id,
                                    'reason' => 'Invoice already exists for period',
                                ];
                                continue;
                            }

                            $dto = new InvoiceGenerationDTO(
                                tenantId: $tenant->tenant_id,
                                tenantRenterId: $tenant->id,
                                periodStart: $periodStart,
                                periodEnd: $periodEnd,
                                dueDate: $periodEnd->copy()->addDays(14)
                            );

                            $result = $this->generateInvoice($dto);

                            if ($result->success) {
                                $results['successful'][] = [
                                    'tenant_id' => $tenant->id,
                                    'invoice_id' => $result->data->id,
                                    'amount' => $result->data->total_amount,
                                ];
                            } else {
                                $results['failed'][] = [
                                    'tenant_id' => $tenant->id,
                                    'error' => $result->message,
                                ];
                            }
                        } catch (\Exception $e) {
                            $results['failed'][] = [
                                'tenant_id' => $tenant->id,
                                'error' => $e->getMessage(),
                            ];

                            $this->handleException($e, [
                                'operation' => 'bulk_invoice_generation',
                                'tenant_id' => $tenant->id,
                            ]);
                        }
                    }
                });

                $this->log('info', 'Bulk invoice generation completed', [
                    'total_processed' => $results['total_processed'],
                    'successful_count' => count($results['successful']),
                    'failed_count' => count($results['failed']),
                    'skipped_count' => count($results['skipped']),
                ]);

                return $this->success($results, 'Bulk invoice generation completed');
            });
        } catch (\Exception $e) {
            $this->handleException($e, [
                'operation' => 'generate_bulk_invoices',
                'tenant_count' => $tenants->count(),
            ]);

            return $this->error('Bulk invoice generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Finalize an invoice, making it immutable.
     *
     * @param Invoice $invoice
     * @return ServiceResponse<Invoice>
     */
    public function finalizeInvoice(Invoice $invoice): ServiceResponse
    {
        try {
            if (auth()->check()) {
                $this->authorize('finalize', $invoice);
            }

            $this->validateTenantOwnership($invoice);

            if (!$invoice->isDraft()) {
                return $this->error('Invoice is not in draft status');
            }

            if ($invoice->total_amount <= 0) {
                return $this->error('Cannot finalize invoice with zero or negative amount');
            }

            return $this->executeInTransaction(function () use ($invoice) {
                $invoice->status = InvoiceStatus::FINALIZED;
                $invoice->finalized_at = now();
                $invoice->save();

                $this->log('info', 'Invoice finalized', [
                    'invoice_id' => $invoice->id,
                    'total_amount' => $invoice->total_amount,
                ]);

                return $this->success($invoice, 'Invoice finalized successfully');
            });
        } catch (\Exception $e) {
            $this->handleException($e, [
                'operation' => 'finalize_invoice',
                'invoice_id' => $invoice->id,
            ]);

            return $this->error('Failed to finalize invoice: ' . $e->getMessage());
        }
    }

    /**
     * Calculate consumption for a billing period.
     *
     * @param Property $property
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @return ServiceResponse<array>
     */
    public function calculateConsumption(
        Property $property, 
        Carbon $periodStart, 
        Carbon $periodEnd
    ): ServiceResponse {
        try {
            $this->authorize('view', $property);
            $this->validateTenantOwnership($property);

            return $this->withMetrics('calculate_consumption', function () use ($property, $periodStart, $periodEnd) {
                $consumption = $this->consumptionService->calculatePropertyConsumption(
                    $property,
                    $periodStart,
                    $periodEnd
                );

                return $this->success($consumption, 'Consumption calculated successfully');
            });
        } catch (\Exception $e) {
            $this->handleException($e, [
                'operation' => 'calculate_consumption',
                'property_id' => $property->id,
            ]);

            return $this->error('Failed to calculate consumption: ' . $e->getMessage());
        }
    }

    /**
     * Get billing history for a tenant.
     *
     * @param Tenant $tenant
     * @param int $months Number of months to retrieve
     * @return ServiceResponse<Collection>
     */
    public function getBillingHistory(Tenant $tenant, int $months = 12): ServiceResponse
    {
        try {
            $this->authorize('view', $tenant);
            $this->validateTenantOwnership($tenant);

            $invoices = Invoice::where('tenant_id', $tenant->tenant_id)
                ->where('tenant_renter_id', $tenant->id)
                ->where('created_at', '>=', now()->subMonths($months))
                ->with(['items.serviceConfiguration.utilityService'])
                ->orderBy('billing_period_start', 'desc')
                ->get();

            return $this->success($invoices, 'Billing history retrieved successfully');
        } catch (\Exception $e) {
            $this->handleException($e, [
                'operation' => 'get_billing_history',
                'tenant_id' => $tenant->id,
            ]);

            return $this->error('Failed to retrieve billing history: ' . $e->getMessage());
        }
    }

    /**
     * Validate billing period dates.
     */
    private function validateBillingPeriod(Carbon $start, Carbon $end): void
    {
        if ($start->gte($end)) {
            throw new \InvalidArgumentException('Period start must be before period end');
        }

        if ($start->diffInDays($end) > 366) {
            throw new \InvalidArgumentException('Billing period cannot exceed 366 days');
        }

        if ($end->isFuture()) {
            throw new \InvalidArgumentException('Billing period end cannot be in the future');
        }
    }

    /**
     * Check if an invoice already exists for the period.
     */
    private function hasExistingInvoice(Tenant $tenant, Carbon $start, Carbon $end): bool
    {
        return Invoice::where('tenant_id', $tenant->tenant_id)
            ->where('tenant_renter_id', $tenant->id)
            ->whereDate('billing_period_start', $start->toDateString())
            ->whereDate('billing_period_end', $end->toDateString())
            ->exists();
    }

    /**
     * Add invoice items based on consumption calculations.
     */
    private function addInvoiceItems(Invoice $invoice, Tenant $tenant, InvoiceGenerationDTO $dto): void
    {
        $property = $tenant->property;

        if (!$property) {
            throw new \RuntimeException('Tenant has no associated property');
        }

        // Get consumption data for the period
        $consumptionResult = $this->consumptionService->calculatePropertyConsumption(
            $property,
            $dto->periodStart,
            $dto->periodEnd
        );

        if (!$consumptionResult->success) {
            throw new \RuntimeException('Failed to calculate consumption: ' . $consumptionResult->message);
        }

        $consumptionData = $consumptionResult->data;
        $billingPeriod = new BillingPeriod($dto->periodStart, $dto->periodEnd);

        // Generate invoice items for each configured service.
        foreach ($consumptionData as $serviceData) {
            $serviceConfiguration = $serviceData['service_configuration'];
            $service = $serviceConfiguration?->utilityService;

            if (!$serviceConfiguration || !$service) {
                continue;
            }

            $consumption = (float) ($serviceData['consumption'] ?? 0.0);
            $consumptionValue = $serviceConfiguration->pricing_model === PricingModel::TIME_OF_USE
                ? UniversalConsumptionData::fromZones(['default' => $consumption])
                : UniversalConsumptionData::fromTotal($consumption);

            $calculation = $this->billingCalculator->calculateBill(
                $serviceConfiguration,
                $consumptionValue,
                $billingPeriod,
            );

            if ($calculation->isZero()) {
                continue;
            }

            $isFixed = $serviceConfiguration->pricing_model === PricingModel::FIXED_MONTHLY;
            $quantity = $isFixed ? 1.0 : max(0.0, $consumption);
            $unit = $isFixed ? 'month' : ($service->unit_of_measurement ?: null);
            $unitPrice = $quantity > 0 ? ($calculation->totalAmount / $quantity) : $calculation->totalAmount;

            $invoice->items()->create([
                'description' => $this->generateItemDescription($serviceData),
                'quantity' => $quantity,
                'unit' => $unit,
                'unit_price' => $unitPrice,
                'total' => $calculation->totalAmount,
                'meter_reading_snapshot' => [
                    'service_configuration_id' => $serviceConfiguration->id,
                    'pricing_model' => $serviceConfiguration->pricing_model?->value,
                    'consumption' => $consumptionValue->toArray(),
                    'calculation' => $calculation->toArray(),
                    'meter_ids' => $serviceData['meter_ids'] ?? [],
                    'readings_count' => $serviceData['readings_count'] ?? null,
                ],
            ]);
        }
    }

    /**
     * Calculate invoice totals from items.
     */
    private function calculateInvoiceTotals(Invoice $invoice): void
    {
        $totalAmount = (float) $invoice->items()->sum('total');
        
        $invoice->update([
            'total_amount' => round($totalAmount, 2),
        ]);
    }

    /**
     * Generate description for invoice item.
     */
    private function generateItemDescription(array $serviceData): string
    {
        $service = $serviceData['service_configuration']->utilityService;
        $consumption = $serviceData['consumption'];
        $unit = $service->unit_of_measurement;

        return "{$service->name} - {$consumption} {$unit}";
    }
}
