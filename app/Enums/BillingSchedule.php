<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Billing schedule types for automated billing cycles.
 * 
 * @package App\Enums
 */
enum BillingSchedule: string
{
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case CUSTOM = 'custom';

    public function getLabel(): string
    {
        return match ($this) {
            self::MONTHLY => __('billing.schedules.monthly'),
            self::QUARTERLY => __('billing.schedules.quarterly'),
            self::CUSTOM => __('billing.schedules.custom'),
        };
    }

    public static function fromString(string $schedule): self
    {
        return match ($schedule) {
            'monthly' => self::MONTHLY,
            'quarterly' => self::QUARTERLY,
            'custom' => self::CUSTOM,
            default => throw new \InvalidArgumentException("Invalid billing schedule: {$schedule}"),
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}