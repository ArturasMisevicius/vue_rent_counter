<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Validation rules for different utility service types.
 * 
 * Provides standardized validation rules for utility services
 * to ensure consistent data validation across the application.
 * 
 * @package App\Enums
 * @author Laravel Development Team
 * @since 1.0.0
 */
enum ServiceValidationRules: string
{
    case ELECTRICITY = 'electricity';
    case WATER = 'water';
    case HEATING = 'heating';
    case GAS = 'gas';

    /**
     * Get validation rules for the service type.
     * 
     * @return array<string, mixed>
     */
    public function getValidationRules(): array
    {
        return match ($this) {
            self::ELECTRICITY => [
                'max_consumption' => 10000,
                'variance_threshold' => 0.5,
                'require_monotonic' => true,
                'allow_negative_consumption' => false,
                'photo_verification_required' => true,
            ],
            self::WATER => [
                'max_consumption' => 1000,
                'variance_threshold' => 0.3,
                'require_monotonic' => true,
                'allow_negative_consumption' => false,
                'photo_verification_required' => false,
            ],
            self::HEATING => [
                'max_consumption' => 5000,
                'variance_threshold' => 0.4,
                'seasonal_adjustment' => true,
                'allow_negative_consumption' => false,
                'photo_verification_required' => false,
            ],
            self::GAS => [
                'max_consumption' => 2000,
                'variance_threshold' => 0.4,
                'require_monotonic' => true,
                'allow_negative_consumption' => false,
                'photo_verification_required' => true,
            ],
        };
    }

    /**
     * Get maximum consumption limit for the service type.
     */
    public function getMaxConsumption(): int
    {
        return match ($this) {
            self::ELECTRICITY => 10000,
            self::WATER => 1000,
            self::HEATING => 5000,
            self::GAS => 2000,
        };
    }

    /**
     * Get variance threshold for the service type.
     */
    public function getVarianceThreshold(): float
    {
        return match ($this) {
            self::ELECTRICITY => 0.5,
            self::WATER => 0.3,
            self::HEATING => 0.4,
            self::GAS => 0.4,
        };
    }

    /**
     * Check if photo verification is required.
     */
    public function requiresPhotoVerification(): bool
    {
        return match ($this) {
            self::ELECTRICITY, self::GAS => true,
            self::WATER, self::HEATING => false,
        };
    }

    /**
     * Check if monotonic readings are required.
     */
    public function requiresMonotonicReadings(): bool
    {
        return match ($this) {
            self::ELECTRICITY, self::WATER, self::GAS => true,
            self::HEATING => false,
        };
    }

    /**
     * Check if seasonal adjustments are supported.
     */
    public function supportsSeasonalAdjustments(): bool
    {
        return match ($this) {
            self::HEATING, self::GAS => true,
            self::ELECTRICITY, self::WATER => false,
        };
    }

    /**
     * Get service type from string.
     */
    public static function fromServiceType(string $serviceType): ?self
    {
        return match ($serviceType) {
            'electricity' => self::ELECTRICITY,
            'water' => self::WATER,
            'heating' => self::HEATING,
            'gas' => self::GAS,
            default => null,
        };
    }

    /**
     * Get all supported service types.
     * 
     * @return array<string>
     */
    public static function getSupportedTypes(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }
}