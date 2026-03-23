<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Enums\UserRole;
use App\Filament\Support\Workspace\WorkspaceContext;
use App\Filament\Support\Workspace\WorkspaceResolver;
use App\Models\User;

trait ResolvesTenantWorkspace
{
    protected function tenantWorkspace(bool $requireOrganization = true): WorkspaceContext
    {
        $workspace = app(WorkspaceResolver::class)->current();

        abort_unless($workspace?->isTenant(), 403);

        if ($requireOrganization) {
            abort_unless($workspace->organizationId !== null, 403);
        }

        return $workspace;
    }

    protected function currentTenant(): User
    {
        $workspace = $this->tenantWorkspace(requireOrganization: false);

        $tenantQuery = User::query()
            ->select(['id', 'organization_id', 'role'])
            ->whereKey($workspace->userId)
            ->where('role', UserRole::TENANT);

        if ($workspace->organizationId !== null) {
            $tenantQuery->where('organization_id', $workspace->organizationId);
        }

        return $tenantQuery->firstOrFail();
    }
}
