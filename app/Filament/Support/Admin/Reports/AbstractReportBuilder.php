<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\Reports;

use App\Models\Property;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

abstract class AbstractReportBuilder
{
    protected function formatCurrency(float $amount, string $currency = 'EUR'): string
    {
        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 2);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 2);

        return trim($currency.' '.(string) $formatter->format($amount));
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

    protected function formatNumber(float $value, int $decimals = 2): string
    {
        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $decimals);

        return (string) $formatter->format($value);
    }

    protected function monthKey(CarbonInterface|string|null $date): string
    {
        if ($date instanceof CarbonInterface) {
            return $date->format('Y-m');
        }

        if (! filled($date)) {
            return '';
        }

        return Carbon::parse((string) $date)->format('Y-m');
    }

    protected function normalizedPaidAmount(float|int|string|null $amountPaid, float|int|string|null $paidAmount): float
    {
        return max((float) $amountPaid, (float) $paidAmount);
    }

    protected function outstandingAmount(float|int|string|null $totalAmount, float $normalizedPaidAmount): float
    {
        return max((float) $totalAmount - $normalizedPaidAmount, 0.0);
    }

    protected function daysOverdue(CarbonInterface|string|null $referenceDate): int
    {
        if (! filled($referenceDate)) {
            return 0;
        }

        $referenceDay = $referenceDate instanceof CarbonInterface
            ? $referenceDate->copy()->startOfDay()
            : Carbon::parse((string) $referenceDate)->startOfDay();
        $today = now()->startOfDay();

        return $referenceDay->greaterThan($today)
            ? 0
            : (int) $referenceDay->diffInDays($today);
    }
}
