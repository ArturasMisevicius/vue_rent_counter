<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceItemSourceType: string
{
    case METER_READING = 'meter_reading';
    case FIXED_SERVICE = 'fixed_service';
    case EXTRA_CHARGE = 'extra_charge';
    case MANUAL_ADJUSTMENT = 'manual_adjustment';
    case DISCOUNT = 'discount';
    case PENALTY = 'penalty';
    case RENT = 'rent';
    case DEPOSIT = 'deposit';
    case CORRECTION = 'correction';
    case TAX = 'tax';
    case ROUNDING = 'rounding';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            fn (self $sourceType): string => $sourceType->value,
            self::cases(),
        );
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            function (array $options, self $sourceType): array {
                $options[$sourceType->value] = $sourceType->label();

                return $options;
            },
            [],
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::METER_READING => __('admin.invoices.source_types.meter_reading'),
            self::FIXED_SERVICE => __('admin.invoices.source_types.fixed_service'),
            self::EXTRA_CHARGE => __('admin.invoices.source_types.extra_charge'),
            self::MANUAL_ADJUSTMENT => __('admin.invoices.source_types.manual_adjustment'),
            self::DISCOUNT => __('admin.invoices.source_types.discount'),
            self::PENALTY => __('admin.invoices.source_types.penalty'),
            self::RENT => __('admin.invoices.source_types.rent'),
            self::DEPOSIT => __('admin.invoices.source_types.deposit'),
            self::CORRECTION => __('admin.invoices.source_types.correction'),
            self::TAX => __('admin.invoices.source_types.tax'),
            self::ROUNDING => __('admin.invoices.source_types.rounding'),
        };
    }
}
