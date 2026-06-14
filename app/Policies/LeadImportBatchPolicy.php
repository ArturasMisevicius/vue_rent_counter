<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LeadImportBatch;
use App\Models\User;
use App\Policies\Concerns\AuthorizesManagerPermissionWrites;

class LeadImportBatchPolicy
{
    use AuthorizesManagerPermissionWrites;

    public function viewAny(User $user): bool
    {
        return $user->isAdminLike();
    }

    public function view(User $user, LeadImportBatch $batch): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $user->isAdminLike()
            && $user->organization_id === $batch->organization_id;
    }

    public function create(User $user): bool
    {
        return $this->canWriteManagedResource($user, 'leads', 'create');
    }

    public function export(User $user): bool
    {
        return $this->canWriteManagedResource($user, 'leads', 'edit');
    }
}
