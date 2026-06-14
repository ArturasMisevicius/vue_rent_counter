<?php

declare(strict_types=1);

namespace App\Filament\Actions\Notifications;

use App\Enums\InvoiceStatus;
use App\Filament\Support\Notifications\DomainNotificationCatalog;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class SendReadingReminders
{
    /**
     * @var list<int>
     */
    private const DEFAULT_DAYS_BEFORE_DEADLINE = [3, 1];

    public function __construct(
        private readonly NotifyTenant $notifyTenant,
    ) {}

    /**
     * @param  list<int>|null  $daysBeforeDeadline
     */
    public function handle(?Organization $organization = null, ?array $daysBeforeDeadline = null): int
    {
        $sent = 0;
        $milestones = $daysBeforeDeadline ?: self::DEFAULT_DAYS_BEFORE_DEADLINE;

        foreach ($milestones as $days) {
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
                    'due_date',
                    'automation_level',
                    'approval_status',
                    'approval_metadata',
                    'snapshot_data',
                    'created_at',
                    'updated_at',
                ])
                ->where('status', InvoiceStatus::DRAFT->value)
                ->where('automation_level', 'reading_request')
                ->whereIn('approval_status', ['waiting_for_readings', 'pending'])
                ->whereDate('due_date', today()->addDays($days))
                ->when(
                    $organization instanceof Organization,
                    fn (Builder $query): Builder => $query->forOrganization($organization->id),
                )
                ->with(['tenant:id,organization_id,name,email,role,status'])
                ->chunkById(100, function (Collection $invoices) use (&$sent, $days): void {
                    foreach ($invoices as $invoice) {
                        if (! $invoice instanceof Invoice || ! $invoice->tenant instanceof User) {
                            continue;
                        }

                        $notification = $this->notifyTenant->handle(
                            tenant: $invoice->tenant,
                            type: DomainNotificationCatalog::READING_REMINDER,
                            subject: $invoice,
                            data: [
                                'days' => $days,
                                'milestone' => $days,
                            ],
                        );

                        if ($notification !== null && $notification->wasRecentlyCreated) {
                            $sent++;
                        }
                    }
                });
        }

        return $sent;
    }
}
