<?php

namespace App\Filament\Actions\Superadmin\Users;

use App\Models\User;
use Illuminate\Support\Facades\Password;

class SendUserPasswordResetAction
{
    public function handle(User $user): void
    {
        Password::sendResetLink([
            'email' => $user->email,
        ]);
    }
}
