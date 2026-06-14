<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Invoices;

use App\Contracts\BillingServiceInterface;
use App\Models\Organization;

final class CalculateInvoice
{
    public function __construct(
        private readonly BillingServiceInterface $billingService,
    ) {}

    /**
     * @param  array{tenant_user_id: int|string, billing_period_start: string, billing_period_end: string, ignore_invoice_id?: int|string}  $attributes
     * @return array{property_id: int, property_name: string, tenant_name: string, items: array<int, array<string, mixed>>, total_amount: string}
     */
    public function handle(Organization $organization, array $attributes): array
    {
        return $this->billingService->previewInvoiceDraft($organization, $attributes);
    }

    /**
     * @param  array{tenant_user_id: int|string, billing_period_start: string, billing_period_end: string, ignore_invoice_id?: int|string}  $attributes
     * @return array{property_id: int, property_name: string, tenant_name: string, items: array<int, array<string, mixed>>, total_amount: string}
     */
    public function __invoke(Organization $organization, array $attributes): array
    {
        return $this->handle($organization, $attributes);
    }
}
