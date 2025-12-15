<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Faq;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Authorization policy for FAQ management.
 *
 * Security requirements:
 * - Only ADMIN and SUPERADMIN roles can manage FAQs
 * - All actions are audited via Laravel's gate system
 * - No tenant scoping (FAQs are global system resources)
 *
 * @see \App\Models\Faq
 * @see \App\Filament\Resources\FaqResource
 */
final class FaqPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any FAQs.
     *
     * @param User $user The authenticated user
     * @return bool True if user can view FAQ list
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine whether the user can view the FAQ.
     *
     * @param User $user The authenticated user
     * @param Faq $faq The FAQ being viewed
     * @return bool True if user can view this FAQ
     */
    public function view(User $user, Faq $faq): bool
    {
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine whether the user can create FAQs.
     *
     * @param User $user The authenticated user
     * @return bool True if user can create FAQs
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine whether the user can update the FAQ.
     *
     * @param User $user The authenticated user
     * @param Faq $faq The FAQ being updated
     * @return bool True if user can update this FAQ
     */
    public function update(User $user, Faq $faq): bool
    {
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine whether the user can delete the FAQ.
     *
     * @param User $user The authenticated user
     * @param Faq $faq The FAQ being deleted
     * @return bool True if user can delete this FAQ
     */
    public function delete(User $user, Faq $faq): bool
    {
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine whether the user can restore the FAQ.
     *
     * @param User $user The authenticated user
     * @param Faq $faq The FAQ being restored
     * @return bool True if user can restore this FAQ
     */
    public function restore(User $user, Faq $faq): bool
    {
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine whether the user can permanently delete the FAQ.
     *
     * @param User $user The authenticated user
     * @param Faq $faq The FAQ being force deleted
     * @return bool True if user can force delete this FAQ
     */
    public function forceDelete(User $user, Faq $faq): bool
    {
        // Only superadmin can permanently delete FAQs
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Determine whether the user can bulk delete FAQs.
     *
     * @param User $user The authenticated user
     * @return bool True if user can bulk delete FAQs
     */
    public function deleteAny(User $user): bool
    {
        return $user->role === UserRole::SUPERADMIN;
    }
}
