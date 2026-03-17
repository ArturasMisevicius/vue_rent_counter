<?php

namespace App\Filament\Support\Admin\Reports;

use App\Models\Property;
use Carbon\CarbonInterface;

abstract class AbstractReportBuilder
{
    protected function formatCurrency(float $amount, string $currency = 'EUR'): string
    {
        return $currency.' '.number_format($amount, 2, '.', '');
    }

    protected function formatDate(CarbonInterface|string|null $date): string
    {
        if ($date instanceof CarbonInterface) {
            return $date->toDateString();
        }

        return filled($date) ? (string) $date : __('dashboard.not_available');
    }

    protected function propertyLabel(?Property $property): string
    {
        if ($property === null) {
            return __('dashboard.not_available');
        }

        $parts = array_filter([
            $property->name,
            $property->unit_number,
            $property->building?->name,
        ]);

        return implode(' · ', $parts);
    }
}
