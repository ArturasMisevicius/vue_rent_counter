<?php

declare(strict_types=1);

namespace App\Contracts\Enhanced;

use App\Models\Invoice;
use App\Models\Property;
use App\Models\Tenant;
use App\Services\ServiceResponse;
use App\Support\Billing\InvoiceGenerationDTO;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Billing Service Interface
 *
 * Defines the contract for billing operations.
 * Enables dependency injection and testing with mocks.
 */
interface BillingServiceInterface
{
    /**
     * Generate a single invoice for a tenant.
     *
     * @param  InvoiceGenerationDTO  $dto  Invoice generation parameters
     * @return ServiceResponse<Invoice>
     */
    public function generateInvoice(InvoiceGenerationDTO $dto): ServiceResponse;

    /**
     * Generate invoices for multiple tenants.
     *
     * @param  Collection<Tenant>  $tenants
     * @return ServiceResponse<array>
     */
    public function generateBulkInvoices(
        Collection $tenants,
        Carbon $periodStart,
        Carbon $periodEnd
    ): ServiceResponse;

    /**
     * Finalize an invoice, making it immutable.
     *
     * @return ServiceResponse<Invoice>
     */
    public function finalizeInvoice(Invoice $invoice): ServiceResponse;

    /**
     * Calculate consumption for a billing period.
     *
     * @return ServiceResponse<array>
     */
    public function calculateConsumption(
        Property $property,
        Carbon $periodStart,
        Carbon $periodEnd
    ): ServiceResponse;

    /**
     * Get billing history for a tenant.
     *
     * @param  int  $months  Number of months to retrieve
     * @return ServiceResponse<Collection>
     */
    public function getBillingHistory(Tenant $tenant, int $months = 12): ServiceResponse;
}
