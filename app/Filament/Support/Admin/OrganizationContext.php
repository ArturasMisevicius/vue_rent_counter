<?php

namespace App\Filament\Support\Admin;

use App\Filament\Support\Workspace\WorkspaceContext;
use App\Filament\Support\Workspace\WorkspaceResolver;
use App\Models\Organization;
use App\Models\User;

class OrganizationContext
{
    public function __construct(
        private readonly WorkspaceResolver $workspaceResolver,
    ) {}

    public function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    public function currentWorkspace(): ?WorkspaceContext
    {
        return $this->workspaceResolver->current();
    }

    public function currentOrganizationId(): ?int
    {
        return $this->currentWorkspace()?->organizationId;
    }

    public function currentPropertyId(): ?int
    {
        return $this->currentWorkspace()?->propertyId;
    }

    public function currentOrganization(): ?Organization
    {
        $organizationId = $this->currentOrganizationId();

        if ($organizationId === null) {
            return null;
        }

        return Organization::query()
            ->select([
                'id',
                'name',
                'slug',
                'status',
                'owner_user_id',
                'created_at',
                'updated_at',
            ])
            ->find($organizationId);
    }
}
