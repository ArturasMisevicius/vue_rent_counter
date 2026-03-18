<?php

namespace App\Filament\Support\Admin\Invoices;

use App\Contracts\BillingServiceInterface;
use App\Models\Organization;

class BulkInvoicePreviewBuilder
{
    public function __construct(
        private readonly BillingServiceInterface $billingService,
    ) {}

    /**
     * @param  array{billing_period_start: string, billing_period_end: string, due_date?: string, selected_assignments?: array<int, string>}  $attributes
     * @return array{valid: array<int, array<string, mixed>>, skipped: array<int, array<string, mixed>>}
     */
    public function handle(Organization $organization, array $attributes): array
    {
        return $this->billingService->previewBulkInvoices($organization, $attributes);
    }
}
