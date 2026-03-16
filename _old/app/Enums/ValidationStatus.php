<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatableLabel;
use Filament\Support\Contracts\HasLabel;

/**
 * Validation status for meter readings.
 */
enum ValidationStatus: string implements HasLabel
{
    use HasTranslatableLabel;

    case PENDING = 'pending';
    case VALIDATED = 'validated';
    case REJECTED = 'rejected';
    case REQUIRES_REVIEW = 'requires_review';

    /**
     * Get the description for this validation status.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::PENDING => __('enums.validation_status.pending_description'),
            self::VALIDATED => __('enums.validation_status.validated_description'),
            self::REJECTED => __('enums.validation_status.rejected_description'),
            self::REQUIRES_REVIEW => __('enums.validation_status.requires_review_description'),
        };
    }

    /**
     * Get the color for this validation status.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::VALIDATED => 'success',
            self::REJECTED => 'danger',
            self::REQUIRES_REVIEW => 'info',
        };
    }

    /**
     * Check if this status indicates the reading is approved.
     */
    public function isApproved(): bool
    {
        return $this === self::VALIDATED;
    }

    /**
     * Check if this status indicates the reading needs attention.
     */
    public function needsAttention(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::REJECTED,
            self::REQUIRES_REVIEW,
        ]);
    }
}