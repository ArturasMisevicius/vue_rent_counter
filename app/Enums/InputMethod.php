<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatableLabel;
use Filament\Support\Contracts\HasLabel;

/**
 * Input methods for meter readings.
 */
enum InputMethod: string implements HasLabel
{
    use HasTranslatableLabel;

    case MANUAL = 'manual';
    case PHOTO_OCR = 'photo_ocr';
    case CSV_IMPORT = 'csv_import';
    case API_INTEGRATION = 'api_integration';
    case ESTIMATED = 'estimated';

    /**
     * Get the description for this input method.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::MANUAL => __('enums.input_method.manual_description'),
            self::PHOTO_OCR => __('enums.input_method.photo_ocr_description'),
            self::CSV_IMPORT => __('enums.input_method.csv_import_description'),
            self::API_INTEGRATION => __('enums.input_method.api_integration_description'),
            self::ESTIMATED => __('enums.input_method.estimated_description'),
        };
    }

    /**
     * Check if this method requires photo validation.
     */
    public function requiresPhoto(): bool
    {
        return $this === self::PHOTO_OCR;
    }

    /**
     * Check if this method is automated.
     */
    public function isAutomated(): bool
    {
        return in_array($this, [
            self::CSV_IMPORT,
            self::API_INTEGRATION,
            self::ESTIMATED,
        ]);
    }

    /**
     * Check if this method requires manual validation.
     */
    public function requiresValidation(): bool
    {
        return in_array($this, [
            self::PHOTO_OCR,
            self::CSV_IMPORT,
            self::ESTIMATED,
        ]);
    }
}