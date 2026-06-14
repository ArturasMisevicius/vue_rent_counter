<?php

namespace App\Filament\Actions\Admin\RentalContracts;

use App\Enums\AuditLogAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Filament\Support\RentalContracts\RentalContractNotificationRecipients;
use App\Models\RentalContract;
use App\Notifications\RentalContracts\RentalContractExpiryReminderNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

class SendContractExpiryReminderAction
{
    private const REMINDER_DAYS = [30, 14, 7];

    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly RentalContractNotificationRecipients $recipients,
    ) {}

    public function handle(?int $organizationId = null): int
    {
        $sent = 0;
        $recipientCache = [];

        foreach (self::REMINDER_DAYS as $days) {
            RentalContract::query()
                ->select([
                    'id',
                    'organization_id',
                    'tenant_id',
                    'property_id',
                    'property_assignment_id',
                    'contract_number',
                    'status',
                    'start_date',
                    'end_date',
                    'tenant_visible',
                    'created_at',
                    'updated_at',
                ])
                ->expiringOn(today()->addDays($days))
                ->when($organizationId !== null, fn ($query) => $query->forOrganization($organizationId))
                ->chunkById(100, function (Collection $contracts) use (&$sent, &$recipientCache, $days): void {
                    foreach ($contracts as $contract) {
                        if (! $contract instanceof RentalContract) {
                            continue;
                        }

                        $organizationId = (int) $contract->organization_id;
                        $recipientCache[$organizationId] ??= $this->recipients->adminAndManagers($organizationId);

                        Notification::send(
                            $recipientCache[$organizationId],
                            new RentalContractExpiryReminderNotification($contract, $days),
                        );

                        $this->auditLogger->record(
                            AuditLogAction::SENT,
                            $contract,
                            [
                                'context' => [
                                    'mutation' => 'rental_contract.expiry_reminder',
                                    'days_until_expiry' => $days,
                                ],
                            ],
                            description: 'Rental contract expiry reminder sent',
                        );

                        $sent++;
                    }
                });
        }

        return $sent;
    }
}
