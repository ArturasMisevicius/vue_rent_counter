<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TenantKycDocument;
use App\Models\User;
use App\Policies\Concerns\AuthorizesManagerPermissionWrites;

class TenantKycDocumentPolicy
{
    use AuthorizesManagerPermissionWrites;

    public function viewAny(User $user): bool
    {
        return $user->isAdminLike() || $user->isTenant();
    }

    public function view(User $user, TenantKycDocument $tenantKycDocument): bool
    {
        if ($user->isTenant()) {
            return $tenantKycDocument->organization_id === $user->organization_id
                && $tenantKycDocument->tenant_id === $user->id;
        }

        return $this->canWriteManagedResource($user, 'tenant_documents', 'edit', $tenantKycDocument->organization_id);
    }

    public function create(User $user): bool
    {
        if ($user->isTenant()) {
            return true;
        }

        return $this->canWriteManagedResource($user, 'tenant_documents', 'create');
    }

    public function replace(User $user, TenantKycDocument $tenantKycDocument): bool
    {
        if ($user->isTenant()) {
            return $this->view($user, $tenantKycDocument);
        }

        return $this->canWriteManagedResource($user, 'tenant_documents', 'edit', $tenantKycDocument->organization_id);
    }

    public function approve(User $user, TenantKycDocument $tenantKycDocument): bool
    {
        return $this->canWriteManagedResource($user, 'tenant_documents', 'edit', $tenantKycDocument->organization_id);
    }

    public function reject(User $user, TenantKycDocument $tenantKycDocument): bool
    {
        return $this->approve($user, $tenantKycDocument);
    }

    public function download(User $user, TenantKycDocument $tenantKycDocument): bool
    {
        return $this->view($user, $tenantKycDocument);
    }
}
