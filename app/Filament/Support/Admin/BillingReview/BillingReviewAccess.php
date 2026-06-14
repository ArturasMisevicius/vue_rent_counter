<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\BillingReview;

use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Models\Organization;
use App\Models\User;

final readonly class BillingReviewAccess
{
    public function __construct(
        private ManagerPermissionService $managerPermissionService,
    ) {}

    public function canAccess(?User $user): bool
    {
        if (! $user instanceof User || $user->isTenant()) {
            return false;
        }

        $organization = $this->organizationFor($user);

        if (! $organization instanceof Organization) {
            return false;
        }

        if ($user->isAdmin() || $user->isSuperadmin()) {
            return true;
        }

        if (! $user->isManager()) {
            return false;
        }

        return $this->managerPermissionService->can($user, $organization, 'billing', 'edit')
            || $this->managerPermissionService->can($user, $organization, 'invoices', 'edit')
            || $this->managerPermissionService->can($user, $organization, 'meter_readings', 'edit');
    }

    public function organizationFor(User $user): ?Organization
    {
        return $user->currentOrganization();
    }
}
