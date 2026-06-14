<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\InvoiceStatus;
use App\Events\InvoiceFinalized;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\Billing\InvoiceReadyForTenantNotification;

final class NotifyTenantInvoiceReady
{
    public function handle(InvoiceFinalized $event): void
    {
        if ($event->tenantUserId === null) {
            return;
        }

        $invoice = Invoice::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'invoice_number',
                'billing_period_start',
                'billing_period_end',
                'status',
            ])
            ->forOrganization($event->organizationId)
            ->whereKey($event->invoiceId)
            ->with([
                'tenant:id,organization_id,name,email,role,status,locale',
            ])
            ->first();

        if (! $invoice instanceof Invoice || ! $invoice->tenant instanceof User) {
            return;
        }

        if ((int) $invoice->tenant_user_id !== $event->tenantUserId) {
            return;
        }

        if ($invoice->status !== InvoiceStatus::FINALIZED || ! $invoice->tenant->isTenant() || ! $invoice->tenant->isActive()) {
            return;
        }

        $invoice->tenant->notify(new InvoiceReadyForTenantNotification($invoice));
    }
}
