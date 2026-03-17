<?php

namespace App\Actions\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;

class RegisterAdminAction
{
    /**
     * @param  array{name: string, email: string, password: string}  $attributes
     */
    public function handle(array $attributes): User
    {
        return User::create([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'password' => $attributes['password'],
            'role' => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
            'locale' => app()->getLocale(),
            'organization_id' => null,
            'last_login_at' => null,
        ]);
    }
}
