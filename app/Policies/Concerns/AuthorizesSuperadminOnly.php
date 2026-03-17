<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait AuthorizesSuperadminOnly
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function view(User $user, mixed $record): bool
    {
        return $user->isSuperadmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user, mixed $record): bool
    {
        return $user->isSuperadmin();
    }

    public function delete(User $user, mixed $record): bool
    {
        return $user->isSuperadmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function restore(User $user, mixed $record): bool
    {
        return $user->isSuperadmin();
    }

    public function forceDelete(User $user, mixed $record): bool
    {
        return $user->isSuperadmin();
    }
}
