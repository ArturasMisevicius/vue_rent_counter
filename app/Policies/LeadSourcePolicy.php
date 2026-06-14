<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LeadSource;
use App\Models\User;
use App\Policies\Concerns\AuthorizesManagerPermissionWrites;

class LeadSourcePolicy
{
    use AuthorizesManagerPermissionWrites;

    public function viewAny(User $user): bool
    {
        return $user->isAdminLike();
    }

    public function view(User $user, LeadSource $source): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $user->isAdminLike()
            && $user->organization_id === $source->organization_id;
    }

    public function create(User $user): bool
    {
        return $this->canWriteManagedResource($user, 'leads', 'create');
    }

    public function update(User $user, LeadSource $source): bool
    {
        return $this->canWriteManagedResource($user, 'leads', 'edit', $source->organization_id);
    }

    public function delete(User $user, LeadSource $source): bool
    {
        return $this->canWriteManagedResource($user, 'leads', 'delete', $source->organization_id);
    }
}
