<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->isSuperadmin()) {
            return null;
        }

        return in_array($ability, ['delete', 'impersonate'], true) ? null : true;
    }

    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isSuperadmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isSuperadmin();
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isSuperadmin() && ! $model->isSuperadmin();
    }

    public function impersonate(User $user, User $model): bool
    {
        return $user->isSuperadmin() && ! $model->isSuperadmin();
    }
}
