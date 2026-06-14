<?php

declare(strict_types=1);

namespace App\Filament\Actions\Notifications;

use App\Filament\Support\Notifications\DomainNotificationCatalog;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class SendInvoiceOverdueReminders
{
    public function __construct(
        private readonly NotifyTenant $notifyTenant,
    ) {}

    public function handle(?Organization $organization = null): int
    {
        $sent = 0;

        Invoice::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'invoice_number',
                'billing_period_start',
                'billing_period_end',
                'status',
                'currency',
                'total_amount',
                'amount_paid',
                'paid_amount',
                'due_date',
                'paid_at',
                'snapshot_data',
                'created_at',
                'updated_at',
            ])
            ->whereOverdueAsOf()
            ->when(
                $organization instanceof Organization,
                fn (Builder $query): Builder => $query->forOrganization($organization->id),
            )
            ->with(['tenant:id,organization_id,name,email,role,status'])
            ->chunkById(100, function (Collection $invoices) use (&$sent): void {
                foreach ($invoices as $invoice) {
                    if (! $invoice instanceof Invoice || ! $invoice->tenant instanceof User) {
                        continue;
                    }

                    $notification = $this->notifyTenant->handle(
                        tenant: $invoice->tenant,
                        type: DomainNotificationCatalog::INVOICE_OVERDUE,
                        subject: $invoice,
                        data: [
                            'days' => $invoice->overdueDays(),
                        ],
                    );

                    if ($notification !== null && $notification->wasRecentlyCreated) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }
}
