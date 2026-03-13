<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Models\User;
use Carbon\Carbon;

/**
 * User State Value Object
 * 
 * Encapsulates user status and state information.
 * Provides a clean interface for checking user status
 * without complex conditional logic.
 */
readonly class UserState
{
    public function __construct(
        private User $user
    ) {}

    /**
     * Check if user is active and not suspended.
     */
    public function isActive(): bool
    {
        return $this->user->is_active && !$this->isSuspended();
    }

    /**
     * Check if user is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->user->suspended_at !== null;
    }

    /**
     * Check if user email is verified.
     */
    public function isEmailVerified(): bool
    {
        return $this->user->email_verified_at !== null;
    }

    /**
     * Check if user has logged in recently (within 30 days).
     */
    public function hasRecentActivity(): bool
    {
        if ($this->user->last_login_at === null) {
            return false;
        }

        return $this->user->last_login_at->isAfter(now()->subDays(30));
    }

    /**
     * Get days since last login.
     */
    public function daysSinceLastLogin(): ?int
    {
        if ($this->user->last_login_at === null) {
            return null;
        }

        return (int) $this->user->last_login_at->diffInDays(now());
    }

    /**
     * Get suspension reason if suspended.
     */
    public function getSuspensionReason(): ?string
    {
        return $this->isSuspended() ? $this->user->suspension_reason : null;
    }

    /**
     * Get suspension date if suspended.
     */
    public function getSuspensionDate(): ?Carbon
    {
        return $this->user->suspended_at;
    }

    /**
     * Check if user can perform actions (active and not suspended).
     */
    public function canPerformActions(): bool
    {
        return $this->isActive() && $this->isEmailVerified();
    }

    /**
     * Get user status as string.
     */
    public function getStatusLabel(): string
    {
        if ($this->isSuspended()) {
            return 'suspended';
        }

        if (!$this->user->is_active) {
            return 'inactive';
        }

        if (!$this->isEmailVerified()) {
            return 'unverified';
        }

        return 'active';
    }

    /**
     * Get user state as array.
     */
    public function toArray(): array
    {
        return [
            'is_active' => $this->isActive(),
            'is_suspended' => $this->isSuspended(),
            'is_email_verified' => $this->isEmailVerified(),
            'has_recent_activity' => $this->hasRecentActivity(),
            'days_since_last_login' => $this->daysSinceLastLogin(),
            'suspension_reason' => $this->getSuspensionReason(),
            'suspension_date' => $this->getSuspensionDate()?->toISOString(),
            'can_perform_actions' => $this->canPerformActions(),
            'status_label' => $this->getStatusLabel(),
        ];
    }
}