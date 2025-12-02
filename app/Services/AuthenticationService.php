<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;

/**
 * Authentication Service
 * 
 * Handles authentication-related business logic including
 * user listing, account validation, and role-based redirects.
 */
final class AuthenticationService
{
    /**
     * Role priority mapping for ordering users.
     */
    private const ROLE_PRIORITY = [
        'superadmin' => 1,
        'admin' => 2,
        'manager' => 3,
        'tenant' => 4,
    ];

    /**
     * Role-to-dashboard route mapping.
     */
    private const DASHBOARD_ROUTES = [
        'superadmin' => '/superadmin/dashboard',
        'admin' => '/admin/dashboard',
        'manager' => '/manager/dashboard',
        'tenant' => '/tenant/dashboard',
    ];

    /**
     * Get active users for login display.
     * 
     * Loads users without global scopes for pre-authentication display.
     * Optimized to only load necessary data and relationships.
     * 
     * @return Collection<int, User>
     */
    public function getActiveUsersForLoginDisplay(): Collection
    {
        return User::withoutGlobalScopes()
            ->select(['id', 'name', 'email', 'role', 'is_active', 'property_id', 'tenant_id'])
            ->with([
                'property:id,address',
                'subscription:id,user_id,status,expires_at',
            ])
            ->active()
            ->orderedByRole()
            ->get();
    }

    /**
     * Check if user account is active.
     */
    public function isAccountActive(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Redirect user to role-appropriate dashboard.
     * 
     * Requirements: 1.1, 8.1
     */
    public function redirectToDashboard(User $user): RedirectResponse
    {
        $route = self::DASHBOARD_ROUTES[$user->role->value] ?? '/';

        return redirect($route);
    }
}
