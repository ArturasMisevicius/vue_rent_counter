<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Collection;

final class LoginFormAccountService
{
    /**
     * Curated demo accounts surfaced on the shared login page.
     *
     * @var array<string, int>
     */
    private const DEMO_EMAIL_PRIORITY = [
        'superadmin@example.com' => 10,
        'admin@example.com' => 20,
        'manager@example.com' => 30,
        'tenant@example.com' => 40,
        'tenant.alina@example.com' => 50,
        'tenant.marius@example.com' => 60,
        'admin@test.com' => 70,
        'manager@test.com' => 80,
        'manager2@test.com' => 90,
        'tenant@test.com' => 100,
        'tenant2@test.com' => 110,
        'tenant3@test.com' => 120,
    ];

    /**
     * Build view data for the Filament login quick-access block.
     *
     * @return array{
     *     panelId: string,
     *     demoPassword: string,
     *     accounts: array<int, array{
     *         name: string,
     *         role: string,
     *         role_key: string,
     *         email: string,
     *         password: string,
     *         panel: string,
     *         route: string
     *     }>
     * }
     */
    public function getViewData(string $panelId): array
    {
        return [
            'panelId' => $panelId,
            'demoPassword' => $this->getDefaultPassword(),
            'accounts' => $this->getAccounts(),
        ];
    }

    /**
     * Build login accounts by role from database users.
     *
     * Uses the first created active user for each role.
     *
     * @return array<int, array{
     *     name: string,
     *     role: string,
     *     role_key: string,
     *     email: string,
     *     password: string,
     *     panel: string,
     *     route: string
     * }>
     */
    private function getAccounts(): array
    {
        $panelRoutes = $this->getPanelRoutes();
        $defaultPassword = $this->getDefaultPassword();
        $demoUsers = $this->getDemoUsers();

        if ($demoUsers->isNotEmpty()) {
            return $demoUsers
                ->sortBy(fn (User $user): int => self::DEMO_EMAIL_PRIORITY[(string) $user->email] ?? PHP_INT_MAX)
                ->map(fn (User $user): array => $this->mapUserToAccount($user, $panelRoutes, $defaultPassword))
                ->values()
                ->all();
        }

        $roles = UserRole::cases();
        $roleValues = array_map(static fn (UserRole $role): string => $role->value, $roles);

        $usersByRole = User::withoutGlobalScopes()
            ->select(['id', 'name', 'email', 'role', 'is_active'])
            ->whereIn('role', $roleValues)
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->groupBy(fn (User $user): string => $this->resolveUserRole($user)->value);

        return collect($roles)
            ->map(function (UserRole $role) use ($usersByRole, $panelRoutes, $defaultPassword): ?array {
                /** @var User|null $user */
                $user = $usersByRole->get($role->value)?->first();

                if (! $user) {
                    return null;
                }

                return $this->mapUserToAccount($user, $panelRoutes, $defaultPassword);
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, User>
     */
    private function getDemoUsers(): Collection
    {
        return User::withoutGlobalScopes()
            ->select(['id', 'name', 'email', 'role', 'is_active'])
            ->where('is_active', true)
            ->whereIn('email', array_keys(self::DEMO_EMAIL_PRIORITY))
            ->get();
    }

    /**
     * @return array<string, string>
     */
    private function getPanelRoutes(): array
    {
        return [
            'admin' => 'login',
            'superadmin' => 'login',
            'tenant' => 'login',
        ];
    }

    /**
     * @param  array<string, string>  $panelRoutes
     * @return array{
     *     name: string,
     *     role: string,
     *     role_key: string,
     *     email: string,
     *     password: string,
     *     panel: string,
     *     route: string
     * }
     */
    private function mapUserToAccount(User $user, array $panelRoutes, string $defaultPassword): array
    {
        $role = $this->resolveUserRole($user);
        $panel = $this->resolvePanelForRole($role);

        return [
            'name' => (string) $user->name,
            'role' => (string) $role->label(),
            'role_key' => $role->value,
            'email' => (string) $user->email,
            'password' => $defaultPassword,
            'panel' => $panel,
            'route' => route($panelRoutes[$panel]),
        ];
    }

    private function getDefaultPassword(): string
    {
        return (string) config('app.demo_login_password', 'password');
    }

    private function resolveUserRole(User $user): UserRole
    {
        return $user->role instanceof UserRole
            ? $user->role
            : UserRole::from((string) $user->role);
    }

    private function resolvePanelForRole(UserRole $role): string
    {
        return match ($role) {
            UserRole::SUPERADMIN => 'superadmin',
            UserRole::TENANT => 'tenant',
            UserRole::ADMIN, UserRole::MANAGER => 'admin',
        };
    }
}
