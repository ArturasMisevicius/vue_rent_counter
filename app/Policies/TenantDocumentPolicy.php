<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TenantDocument;
use App\Models\User;
use App\Policies\Concerns\AuthorizesManagerPermissionWrites;

class TenantDocumentPolicy
{
    use AuthorizesManagerPermissionWrites;

    public function viewAny(User $user): bool
    {
        return $user->isAdminLike() || $user->isTenant();
    }

    public function view(User $user, TenantDocument $tenantDocument): bool
    {
        if ($user->isAdminLike()) {
            return $user->organization_id === $tenantDocument->organization_id;
        }

        return $user->isTenant()
            && $tenantDocument->tenant_id === $user->id
            && $tenantDocument->organization_id === $user->organization_id
            && $tenantDocument->isVisibleToTenantPortal();
    }

    public function download(User $user, TenantDocument $tenantDocument): bool
    {
        return $this->view($user, $tenantDocument);
    }

    public function create(User $user): bool
    {
        if ($user->isTenant()) {
            return true;
        }

        return $this->canWriteManagedResource($user, 'tenant_documents', 'create');
    }

    public function update(User $user, TenantDocument $tenantDocument): bool
    {
        return $this->canWriteManagedResource($user, 'tenant_documents', 'edit', $tenantDocument->organization_id);
    }

    public function replace(User $user, TenantDocument $tenantDocument): bool
    {
        return $this->update($user, $tenantDocument);
    }

    public function verify(User $user, TenantDocument $tenantDocument): bool
    {
        return $this->update($user, $tenantDocument);
    }

    public function reject(User $user, TenantDocument $tenantDocument): bool
    {
        return $this->update($user, $tenantDocument);
    }

    public function archive(User $user, TenantDocument $tenantDocument): bool
    {
        return $this->canWriteManagedResource($user, 'tenant_documents', 'delete', $tenantDocument->organization_id);
    }

    public function delete(User $user, TenantDocument $tenantDocument): bool
    {
        return $this->archive($user, $tenantDocument);
    }
}
