<?php

namespace App\Support\Auth;

use App\Models\User;

class LoginDemoAccountPresenter
{
    /**
     * @var list<string>
     */
    private const CURATED_EMAILS = [
        'superadmin@example.com',
        'admin@example.com',
        'manager@example.com',
        'tenant.alina@example.com',
        'tenant.marius@example.com',
    ];

    /**
     * @return list<array{name: string, email: string, password: string, role: string}>
     */
    public function accounts(): array
    {
        $usersByEmail = User::query()
            ->select(['id', 'name', 'email', 'role'])
            ->whereIn('email', self::CURATED_EMAILS)
            ->get()
            ->keyBy('email');

        return collect(self::CURATED_EMAILS)
            ->filter(fn (string $email): bool => $usersByEmail->has($email))
            ->map(function (string $email) use ($usersByEmail): array {
                /** @var User $user */
                $user = $usersByEmail->get($email);

                return [
                    'name' => $user->name,
                    'email' => $user->email,
                    'password' => 'password',
                    'role' => $user->role->label(),
                ];
            })
            ->values()
            ->all();
    }
}
