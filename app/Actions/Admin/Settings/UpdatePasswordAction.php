<?php

namespace App\Actions\Admin\Settings;

use App\Models\User;

class UpdatePasswordAction
{
    public function handle(User $user, string $password): void
    {
        $user->forceFill([
            'password' => $password,
        ])->save();
    }
}
