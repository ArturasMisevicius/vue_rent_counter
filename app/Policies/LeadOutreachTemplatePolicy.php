<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LeadOutreachTemplate;
use App\Models\User;
use App\Policies\Concerns\AuthorizesManagerPermissionWrites;

class LeadOutreachTemplatePolicy
{
    use AuthorizesManagerPermissionWrites;

    public function viewAny(User $user): bool
    {
        return $user->isAdminLike();
    }

    public function view(User $user, LeadOutreachTemplate $template): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $user->isAdminLike()
            && $user->organization_id === $template->organization_id;
    }

    public function create(User $user): bool
    {
        return $this->canWriteManagedResource($user, 'leads', 'create');
    }

    public function update(User $user, LeadOutreachTemplate $template): bool
    {
        return $this->canWriteManagedResource($user, 'leads', 'edit', $template->organization_id);
    }

    public function delete(User $user, LeadOutreachTemplate $template): bool
    {
        return $this->canWriteManagedResource($user, 'leads', 'delete', $template->organization_id);
    }
}
