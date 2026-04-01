<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin() || $user->isAdmin() || $user->isManager();
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($this->sharesOrganization($user, $project) && ($user->isAdmin() || $user->isManager())) {
            return true;
        }

        return $project->teamMembers()
            ->where('users.id', $user->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin() || $user->isAdmin();
    }

    public function update(User $user, Project $project): bool
    {
        if ($project->isReadOnly()) {
            return false;
        }

        if ($user->isSuperadmin()) {
            return true;
        }

        if (! $this->sharesOrganization($user, $project)) {
            return false;
        }

        return $user->isAdmin() || $project->manager_id === $user->id;
    }

    public function delete(User $user, Project $project): bool
    {
        if (! $user->isSuperadmin()) {
            return false;
        }

        return ! $project->timeEntries()->exists()
            && ! $project->tasks()->where('status', 'completed')->exists()
            && ! $project->invoiceItems()->whereNull('voided_at')->exists();
    }

    public function forceTransitionStatus(User $user, Project $project): bool
    {
        return $user->isSuperadmin();
    }

    public function approve(User $user, Project $project): bool
    {
        return $user->isSuperadmin()
            || ($this->sharesOrganization($user, $project) && $user->isAdmin())
            || $project->organization?->owner_user_id === $user->id;
    }

    public function generateCostPassthrough(User $user, Project $project): bool
    {
        return $this->approve($user, $project);
    }

    public function viewCosts(User $user, Project $project): bool
    {
        return $user->isSuperadmin()
            || ($this->sharesOrganization($user, $project) && $user->isAdmin())
            || $project->organization?->owner_user_id === $user->id
            || $project->manager_id === $user->id;
    }

    private function sharesOrganization(User $user, Project $project): bool
    {
        return $user->organization_id !== null
            && $user->organization_id === $project->organization_id;
    }
}
