<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\TenantMoveOut\Concerns;

use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Models\Organization;
use App\Models\User;

trait AuthorizesTenantMoveOut
{
    private function authorizeTenantMoveOut(User $actor, int $organizationId, string $permissionAction = 'edit'): void
    {
        if ($actor->isSuperadmin()) {
            return;
        }

        if ($actor->isAdmin() && (int) $actor->organization_id === $organizationId) {
            return;
        }

        if (! $actor->isManager() || (int) $actor->organization_id !== $organizationId) {
            abort(403);
        }

        $organization = Organization::query()
            ->select(['id', 'name'])
            ->findOrFail($organizationId);

        abort_unless(
            app(ManagerPermissionService::class)->can($actor, $organization, 'tenants', $permissionAction),
            403,
        );
    }
}
