<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Enums\UserRole;
use App\Filament\Support\Workspace\WorkspaceContext;
use App\Filament\Support\Workspace\WorkspaceResolver;
use App\Models\User;

trait ResolvesTenantWorkspace
{
    protected function tenantWorkspace(): WorkspaceContext
    {
        $workspace = app(WorkspaceResolver::class)->current();

        abort_unless(
            $workspace?->isTenant() && $workspace->organizationId !== null,
            403,
        );

        return $workspace;
    }

    protected function currentTenant(): User
    {
        $workspace = $this->tenantWorkspace();

        return User::query()
            ->select(['id', 'organization_id', 'role'])
            ->whereKey($workspace->userId)
            ->where('organization_id', $workspace->organizationId)
            ->where('role', UserRole::TENANT)
            ->firstOrFail();
    }
}
