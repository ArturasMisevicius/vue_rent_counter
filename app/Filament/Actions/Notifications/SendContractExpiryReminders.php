<?php

declare(strict_types=1);

namespace App\Filament\Actions\Notifications;

use App\Filament\Support\Notifications\DomainNotificationCatalog;
use App\Models\Organization;
use App\Models\RentalContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class SendContractExpiryReminders
{
    /**
     * @var list<int>
     */
    private const MILESTONES = [30, 14, 7];

    public function __construct(
        private readonly NotifyOrganizationAdmins $notifyOrganizationAdmins,
        private readonly NotifyOrganizationManagers $notifyOrganizationManagers,
    ) {}

    public function handle(?Organization $organization = null): int
    {
        $sent = 0;
        $organizations = [];

        foreach (self::MILESTONES as $days) {
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
                ->when(
                    $organization instanceof Organization,
                    fn (Builder $query): Builder => $query->forOrganization($organization->id),
                )
                ->chunkById(100, function (Collection $contracts) use (&$sent, &$organizations, $days): void {
                    foreach ($contracts as $contract) {
                        if (! $contract instanceof RentalContract) {
                            continue;
                        }

                        $organization = $organizations[$contract->organization_id] ??= Organization::query()
                            ->select(['id', 'name', 'owner_user_id'])
                            ->find($contract->organization_id);

                        if (! $organization instanceof Organization) {
                            continue;
                        }

                        $data = [
                            'days' => $days,
                            'milestone' => $days,
                        ];

                        $sent += $this->notifyOrganizationAdmins
                            ->handle($organization, DomainNotificationCatalog::CONTRACT_EXPIRING, $contract, $data)
                            ->filter->wasRecentlyCreated
                            ->count();
                        $sent += $this->notifyOrganizationManagers
                            ->handle($organization, DomainNotificationCatalog::CONTRACT_EXPIRING, $contract, $data)
                            ->filter->wasRecentlyCreated
                            ->count();
                    }
                });
        }

        return $sent;
    }
}
