<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum MeterReadingStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CORRECTED = 'corrected';
    case VOIDED = 'voided';

    /**
     * @return array<int, string>
     */
    public static function activeValues(): array
    {
        return self::onlyValues(
            self::DRAFT,
            self::SUBMITTED,
            self::APPROVED,
            self::CORRECTED,
        );
    }

    /**
     * @return array<int, string>
     */
    public static function invoiceCalculationValues(): array
    {
        return self::onlyValues(
            self::APPROVED,
            self::CORRECTED,
        );
    }

    public static function fromValidationStatus(MeterReadingValidationStatus $status): self
    {
        return match ($status) {
            MeterReadingValidationStatus::VALID => self::APPROVED,
            MeterReadingValidationStatus::REJECTED => self::REJECTED,
            MeterReadingValidationStatus::VOID => self::VOIDED,
            MeterReadingValidationStatus::PENDING,
            MeterReadingValidationStatus::FLAGGED => self::SUBMITTED,
        };
    }
}
