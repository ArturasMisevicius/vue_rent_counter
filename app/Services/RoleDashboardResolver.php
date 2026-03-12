<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

/**
 * Canonical resolver for role-to-dashboard routing and role entry checks.
 */
final class RoleDashboardResolver
{
    /**
     * @var array<string, string>
     */
    private const DASHBOARD_ROUTES = [
        UserRole::SUPERADMIN->value => 'superadmin.dashboard',
        UserRole::ADMIN->value => 'admin.dashboard',
        UserRole::MANAGER->value => 'manager.dashboard',
        UserRole::TENANT->value => 'tenant.dashboard',
    ];

    public function dashboardRouteNameFor(User $user): string
    {
        return $this->dashboardRouteNameForRole($user->role);
    }

    public function dashboardRouteNameForRole(UserRole $role): string
    {
        $routeName = self::DASHBOARD_ROUTES[$role->value] ?? null;

        if ($routeName === null) {
            throw new InvalidArgumentException("Unsupported role [{$role->value}] for dashboard resolution.");
        }

        return $routeName;
    }

    public function redirectToDashboard(User $user): RedirectResponse
    {
        return redirect()->route($this->dashboardRouteNameFor($user));
    }

    public function redirectToRoleDashboard(UserRole $role): RedirectResponse
    {
        return redirect()->route($this->dashboardRouteNameForRole($role));
    }

    public function canAccessRoleEntry(User $user, UserRole $expectedRole): bool
    {
        return $user->role === $expectedRole;
    }
}
