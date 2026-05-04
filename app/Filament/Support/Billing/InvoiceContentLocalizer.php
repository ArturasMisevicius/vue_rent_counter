<?php

declare(strict_types=1);

namespace App\Filament\Support\Billing;

final class InvoiceContentLocalizer
{
    /**
     * @var array<string, string>
     */
    private const LINE_ITEM_KEYS = [
        'cold water usage' => 'tenant.invoice_line_items.cold_water_usage',
        'electricity' => 'tenant.invoice_line_items.electricity',
        'electricity charge' => 'tenant.invoice_line_items.electricity_charge',
        'electricity usage' => 'tenant.invoice_line_items.electricity_usage',
        'final water usage' => 'tenant.invoice_line_items.final_water_usage',
        'heating' => 'tenant.invoice_line_items.heating',
        'heating charge' => 'tenant.invoice_line_items.heating_charge',
        'heating usage' => 'tenant.invoice_line_items.heating_usage',
        'maintenance fee' => 'tenant.invoice_line_items.maintenance_fee',
        'shared heating' => 'tenant.invoice_line_items.shared_heating',
        'shared services fee' => 'tenant.invoice_line_items.shared_services_fee',
        'water' => 'tenant.invoice_line_items.water',
        'water base charge' => 'tenant.invoice_line_items.water_base_charge',
        'water charge' => 'tenant.invoice_line_items.water_charge',
        'water supply' => 'tenant.invoice_line_items.water_supply',
        'water usage' => 'tenant.invoice_line_items.water_usage',
    ];

    /**
     * @var array<string, string>
     */
    private const UNIT_KEYS = [
        'collection' => 'tenant.invoice_units.collection',
        'day' => 'tenant.invoice_units.day',
        'kWh' => 'tenant.invoice_units.kwh',
        'm3' => 'tenant.invoice_units.m3',
        'MWh' => 'tenant.invoice_units.mwh',
        'month' => 'tenant.invoice_units.month',
        'unit' => 'tenant.invoice_units.unit',
    ];

    public function lineItemDescription(?string $description): string
    {
        $normalizedDescription = self::normalized($description);

        if ($normalizedDescription === '') {
            return '';
        }

        $translationKey = self::LINE_ITEM_KEYS[mb_strtolower($normalizedDescription)] ?? null;

        return $translationKey === null ? $normalizedDescription : __($translationKey);
    }

    public function unit(?string $unit): string
    {
        $normalizedUnit = self::normalized($unit);

        if ($normalizedUnit === '') {
            return '';
        }

        $translationKey = self::UNIT_KEYS[$normalizedUnit] ?? null;

        return $translationKey === null ? $normalizedUnit : __($translationKey);
    }

    private static function normalized(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return (string) preg_replace('/\s+/u', ' ', trim($value));
    }
}
