<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;
use App\Policies\Concerns\AuthorizesSuperadminOnly;

class SubscriptionPolicy
{
    use AuthorizesSuperadminOnly;

    public function extend(User $user, Subscription $subscription): bool
    {
        return $user->isSuperadmin();
    }

    public function upgrade(User $user, Subscription $subscription): bool
    {
        return $user->isSuperadmin();
    }

    public function suspend(User $user, Subscription $subscription): bool
    {
        return $user->isSuperadmin();
    }

    public function cancel(User $user, Subscription $subscription): bool
    {
        return $user->isSuperadmin();
    }
}
