<?php

namespace App\Filament\Actions\Superadmin\Users;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class DeleteUserAction
{
    public function handle(User $user): void
    {
        $reason = $user->superadminDeletionBlockedReason();

        if ($reason !== null) {
            throw ValidationException::withMessages([
                'user' => $reason,
            ]);
        }

        $user->delete();
    }
}
