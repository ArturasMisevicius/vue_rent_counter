<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\BillingIntegrity;

use App\Enums\UserRole;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final readonly class BillingIntegrityActionGuard
{
    public function __construct(
        private ManagerPermissionService $managerPermissionService,
    ) {}

    public function ensureCanManage(?User $actor, int $organizationId): User
    {
        if (! $actor instanceof User || $actor->isTenant()) {
            $this->deny();
        }

        if ($actor->isSuperadmin()) {
            return $actor;
        }

        $organization = $actor->currentOrganization();

        if (! $organization instanceof Organization || (int) $organization->id !== $organizationId) {
            $this->deny();
        }

        if ($actor->isAdmin() || $organization->owner_user_id === $actor->id) {
            return $actor;
        }

        if (! $actor->hasOrganizationRole($organization, UserRole::MANAGER)) {
            $this->deny();
        }

        $allowed = $this->managerPermissionService->can($actor, $organization, 'billing', 'edit')
            || $this->managerPermissionService->can($actor, $organization, 'invoices', 'edit')
            || $this->managerPermissionService->can($actor, $organization, 'meter_readings', 'edit');

        if (! $allowed) {
            $this->deny();
        }

        return $actor;
    }

    public function ensureReason(string $reason): string
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => __('admin.billing_cleanup.errors.reason_required'),
            ]);
        }

        return $reason;
    }

    private function deny(): never
    {
        throw ValidationException::withMessages([
            'billing_cleanup' => __('admin.billing_cleanup.errors.forbidden'),
        ]);
    }
}
