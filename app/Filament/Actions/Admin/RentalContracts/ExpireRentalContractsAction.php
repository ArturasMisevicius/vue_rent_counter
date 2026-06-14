<?php

namespace App\Filament\Actions\Admin\RentalContracts;

use App\Enums\AuditLogAction;
use App\Enums\RentalContractStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Filament\Support\RentalContracts\RentalContractNotificationRecipients;
use App\Models\RentalContract;
use App\Notifications\RentalContracts\RentalContractExpiredNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

class ExpireRentalContractsAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly RentalContractNotificationRecipients $recipients,
    ) {}

    public function handle(?int $organizationId = null): int
    {
        $expiredCount = 0;
        $recipientCache = [];

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
                'updated_by_user_id',
                'created_at',
                'updated_at',
            ])
            ->expiredUnmarked()
            ->when($organizationId !== null, fn ($query) => $query->forOrganization($organizationId))
            ->chunkById(100, function (Collection $contracts) use (&$expiredCount, &$recipientCache): void {
                foreach ($contracts as $contract) {
                    if (! $contract instanceof RentalContract) {
                        continue;
                    }

                    $before = $contract->getOriginal();

                    $contract->forceFill([
                        'status' => RentalContractStatus::EXPIRED,
                    ])->save();

                    $this->auditLogger->record(
                        AuditLogAction::UPDATED,
                        $contract,
                        [
                            'before' => $before,
                            'after' => $contract->getAttributes(),
                            'context' => ['mutation' => 'rental_contract.expired'],
                        ],
                        description: 'Rental contract expired',
                    );

                    $organizationId = (int) $contract->organization_id;
                    $recipientCache[$organizationId] ??= $this->recipients->adminAndManagers($organizationId);

                    Notification::send(
                        $recipientCache[$organizationId],
                        new RentalContractExpiredNotification($contract),
                    );

                    $expiredCount++;
                }
            });

        return $expiredCount;
    }
}
