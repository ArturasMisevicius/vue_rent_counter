<?php

namespace App\Exceptions\ManagerPermissions;

use App\Models\Organization;
use App\Models\User;
use RuntimeException;

class UserIsNotManagerException extends RuntimeException
{
    public static function forUser(User $user, Organization $organization): self
    {
        return new self("User [{$user->id}] is not a manager for organization [{$organization->id}].");
    }
}
