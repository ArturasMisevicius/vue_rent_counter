<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Invoices;

use App\Contracts\BillingServiceInterface;
use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Models\Invoice;
use App\Models\User;

final class PrepareReadingRequestInvoiceAction
{
    public function __construct(
        private readonly BillingServiceInterface $billingService,
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
    ) {}

    public function handle(Invoice $invoice, ?User $actor = null): Invoice
    {
        $this->subscriptionLimitGuard->ensureCanWrite($invoice->organization_id);

        return $this->billingService->prepareReadingRequestInvoice($invoice, $actor);
    }
}
