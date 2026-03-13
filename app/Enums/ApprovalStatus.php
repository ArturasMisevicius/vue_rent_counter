<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Approval status for invoices and billing operations.
 */
enum ApprovalStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case REQUIRES_REVIEW = 'requires_review';
    case AUTO_APPROVED = 'auto_approved';

    /**
     * Get the human-readable label for the status.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => __('enums.approval_status.pending'),
            self::APPROVED => __('enums.approval_status.approved'),
            self::REJECTED => __('enums.approval_status.rejected'),
            self::REQUIRES_REVIEW => __('enums.approval_status.requires_review'),
            self::AUTO_APPROVED => __('enums.approval_status.auto_approved'),
        };
    }

    /**
     * Get the color for the status badge.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::REQUIRES_REVIEW => 'info',
            self::AUTO_APPROVED => 'success',
        };
    }

    /**
     * Check if the status indicates approval is complete.
     */
    public function isApproved(): bool
    {
        return in_array($this, [self::APPROVED, self::AUTO_APPROVED]);
    }

    /**
     * Check if the status indicates rejection.
     */
    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }

    /**
     * Check if the status is still pending action.
     */
    public function isPending(): bool
    {
        return in_array($this, [self::PENDING, self::REQUIRES_REVIEW]);
    }
}