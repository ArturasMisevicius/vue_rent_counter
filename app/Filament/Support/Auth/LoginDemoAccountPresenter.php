<?php

namespace App\Filament\Support\Auth;

use App\Models\User;

class LoginDemoAccountPresenter
{
    /**
     * @return list<array{name: string, email: string, password: string, role: string}>
     */
    public function accounts(): array
    {
        return User::query()
            ->forLoginDemoTable()
            ->get()
            ->map(function (User $user): array {
                return [
                    'name' => $user->name,
                    'email' => $user->email,
                    'password' => 'password',
                    'role' => $user->role->label(),
                ];
            })
            ->all();
    }
}
