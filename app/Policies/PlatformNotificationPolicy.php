<?php

namespace App\Policies;

use App\Models\PlatformNotification;
use App\Models\User;

class PlatformNotificationPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, PlatformNotification $platformNotification): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, PlatformNotification $platformNotification): bool
    {
        return false;
    }
}
