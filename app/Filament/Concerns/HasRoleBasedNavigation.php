<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Provides role-based navigation visibility for Filament resources.
 *
 * This trait centralizes navigation visibility logic based on user roles,
 * ensuring consistent behavior across resources.
 */
trait HasRoleBasedNavigation
{
    /**
     * Determine if the resource should be registered in navigation.
     *
     * Hides the resource from tenant users while showing it to
     * managers, admins, and superadmins.
     *
     * @return bool Whether the resource should appear in navigation
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->role !== UserRole::TENANT;
    }
}
