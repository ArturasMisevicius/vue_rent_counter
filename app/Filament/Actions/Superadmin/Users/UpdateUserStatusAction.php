<?php

namespace App\Filament\Actions\Superadmin\Users;

use App\Enums\UserStatus;
use App\Models\User;

class UpdateUserStatusAction
{
    public function handle(User $user, UserStatus $status): User
    {
        $user->update([
            'status' => $status,
            'suspended_at' => $status === UserStatus::SUSPENDED ? now() : null,
            'suspension_reason' => null,
        ]);

        return $user->refresh();
    }
}
