<?php

namespace App\Filament\Support\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Str;

class LoginDemoAccountPresenter
{
    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     accounts: list<array{name: string, email: string, password: string, role: string, role_key: string}>
     * }>
     */
    public function accounts(): array
    {
        return $this->groupAccounts(
            User::query()
                ->forLoginDemoTable()
                ->get()
                ->map(function (User $user): array {
                    return [
                        'name' => $user->name,
                        'email' => $user->email,
                        'password' => 'password',
                        'role' => $user->role->label(),
                        'role_key' => $user->role->value,
                    ];
                })
                ->all(),
        );
    }

    /**
     * @param  list<array{name: string, email: string, password: string, role: string, role_key?: string}>  $accounts
     * @return list<array{
     *     key: string,
     *     label: string,
     *     accounts: list<array{name: string, email: string, password: string, role: string, role_key: string}>
     * }>
     */
    public function groupAccounts(array $accounts): array
    {
        $groupedAccounts = [];

        foreach ($this->orderedRoles() as $role) {
            $groupedAccounts[$role->value] = [
                'key' => $role->value,
                'label' => $role->label(),
                'accounts' => [],
            ];
        }

        foreach ($accounts as $account) {
            $roleKey = $this->resolveRoleKey($account);

            if ($roleKey === null) {
                continue;
            }

            if (! array_key_exists($roleKey, $groupedAccounts)) {
                $groupedAccounts[$roleKey] = [
                    'key' => $roleKey,
                    'label' => $account['role'],
                    'accounts' => [],
                ];
            }

            $groupedAccounts[$roleKey]['accounts'][] = [
                ...$account,
                'role_key' => $roleKey,
            ];
        }

        return array_values(array_filter(
            $groupedAccounts,
            fn (array $group): bool => $group['accounts'] !== [],
        ));
    }

    /**
     * @return list<UserRole>
     */
    private function orderedRoles(): array
    {
        return [
            UserRole::SUPERADMIN,
            UserRole::ADMIN,
            UserRole::MANAGER,
            UserRole::TENANT,
        ];
    }

    /**
     * @param  array{name: string, email: string, password: string, role: string, role_key?: string}  $account
     */
    private function resolveRoleKey(array $account): ?string
    {
        $candidate = Str::of((string) ($account['role_key'] ?? $account['role']))
            ->lower()
            ->replace([' ', '-'], '_')
            ->value();

        foreach (UserRole::cases() as $role) {
            $labelCandidate = Str::of($role->label())
                ->lower()
                ->replace([' ', '-'], '_')
                ->value();

            if ($candidate === $role->value || $candidate === $labelCandidate) {
                return $role->value;
            }
        }

        return null;
    }
}
