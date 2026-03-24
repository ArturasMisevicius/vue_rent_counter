<?php

namespace App\Filament\Support\Admin\Invoices;

use App\Contracts\BillingServiceInterface;
use App\Models\Organization;

class InvoiceDraftPreviewBuilder
{
    public function __construct(
        private readonly BillingServiceInterface $billingService,
    ) {}

    /**
     * @param  array{tenant_user_id: int|string, billing_period_start: string, billing_period_end: string}  $attributes
     * @return array{property_id: int, property_name: string, tenant_name: string, items: array<int, array<string, mixed>>, total_amount: string}
     */
    public function handle(Organization $organization, array $attributes): array
    {
        return $this->billingService->previewInvoiceDraft($organization, $attributes);
    }
}
