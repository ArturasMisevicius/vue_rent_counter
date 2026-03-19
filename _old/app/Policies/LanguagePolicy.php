<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Filament\Resources\LanguageResource;
use App\Models\Language;
use App\Models\User;

/**
 * Language Policy
 *
 * Defines authorization rules for Language model operations.
 * Only superadmins can manage languages.
 *
 * SECURITY FEATURES:
 * - Centralized authorization logic
 * - Consistent permission checks
 * - Type-safe role comparison
 * - Testable authorization rules
 *
 * @see Language
 * @see LanguageResource
 */
final class LanguagePolicy
{
    /**
     * Determine if the user can view any languages.
     *
     * SECURITY: Only superadmins can view the language list.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine if the user can view the language.
     *
     * SECURITY: Only superadmins can view individual languages.
     */
    public function view(User $user, Language $language): bool
    {
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine if the user can create languages.
     *
     * SECURITY: Only superadmins can create new languages.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine if the user can update the language.
     *
     * SECURITY: Only superadmins can update languages.
     */
    public function update(User $user, Language $language): bool
    {
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine if the user can delete the language.
     *
     * SECURITY: Only superadmins can delete languages.
     */
    public function delete(User $user, Language $language): bool
    {
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine if the user can restore the language.
     *
     * SECURITY: Only superadmins can restore soft-deleted languages.
     */
    public function restore(User $user, Language $language): bool
    {
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine if the user can permanently delete the language.
     *
     * SECURITY: Only superadmins can force delete languages.
     */
    public function forceDelete(User $user, Language $language): bool
    {
        return $user->role === UserRole::SUPERADMIN;
    }
}
