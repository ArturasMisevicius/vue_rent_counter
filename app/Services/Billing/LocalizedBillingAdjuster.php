<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\ValueObjects\BillingPeriod;
use App\ValueObjects\UniversalCalculationResult;
use InvalidArgumentException;

final class LocalizedBillingAdjuster
{
    /**
     * @param  array<string, mixed>  $rateSchedule
     */
    public function apply(
        UniversalCalculationResult $result,
        array $rateSchedule,
        BillingPeriod $billingPeriod
    ): UniversalCalculationResult {
        $localization = $rateSchedule['localization'] ?? null;

        if (! is_array($localization) || $localization === []) {
            return $result;
        }

        $precision = $this->normalizePrecision($localization['money_precision'] ?? 2);
        $roundingMode = $this->normalizeRoundingMode($localization['rounding_mode'] ?? 'half_up');

        $adjustments = $result->adjustments;
        $runningTotal = (float) $result->totalAmount;

        foreach ($this->normalizeFixedCharges($localization['fixed_charges'] ?? []) as $charge) {
            $runningTotal += $charge['amount'];
            $adjustments[] = [
                'type' => 'localized_fixed_charge',
                'description' => $charge['name'],
                'amount' => $charge['amount'],
            ];
        }

        foreach ($this->normalizeSurcharges($localization['surcharges'] ?? []) as $surcharge) {
            $surchargeAmount = $runningTotal * ($surcharge['percentage'] / 100);
            $runningTotal += $surchargeAmount;
            $adjustments[] = [
                'type' => 'localized_surcharge',
                'description' => $surcharge['name'],
                'amount' => $surchargeAmount,
            ];
        }

        $minimumCharge = $this->normalizeNonNegativeFloat($localization['minimum_charge'] ?? 0.0, 'minimum_charge');

        if ($minimumCharge > 0 && $runningTotal < $minimumCharge) {
            $minimumTopUp = $minimumCharge - $runningTotal;
            $runningTotal += $minimumTopUp;
            $adjustments[] = [
                'type' => 'localized_minimum_charge',
                'description' => 'Minimum charge adjustment',
                'amount' => $minimumTopUp,
            ];
        }

        $taxRate = $this->normalizeNonNegativeFloat($localization['tax_rate'] ?? 0.0, 'tax_rate');

        if ($taxRate > 0) {
            $taxAmount = $runningTotal * ($taxRate / 100);
            $runningTotal += $taxAmount;
            $adjustments[] = [
                'type' => 'localized_tax',
                'description' => 'Tax',
                'amount' => $taxAmount,
            ];
        }

        $roundedTotal = $this->roundAmount($runningTotal, $precision, $roundingMode);

        $localizedDetails = [
            'applied' => true,
            'locale' => isset($localization['locale']) && is_string($localization['locale'])
                ? $localization['locale']
                : null,
            'billing_period_days' => $billingPeriod->getDays(),
            'rounding_mode' => $roundingMode,
            'money_precision' => $precision,
            'subtotal_before_rounding' => $runningTotal,
            'subtotal_after_rounding' => $roundedTotal,
        ];

        return new UniversalCalculationResult(
            totalAmount: $roundedTotal,
            baseAmount: $result->baseAmount,
            adjustments: $adjustments,
            consumptionAmount: $result->consumptionAmount,
            fixedAmount: $result->fixedAmount,
            tariffSnapshot: $result->tariffSnapshot,
            calculationDetails: array_merge($result->calculationDetails, [
                'localization' => $localizedDetails,
            ]),
        );
    }

    private function normalizePrecision(mixed $value): int
    {
        if (! is_numeric($value)) {
            return 2;
        }

        $precision = (int) $value;

        if ($precision < 0 || $precision > 6) {
            throw new InvalidArgumentException('money_precision must be between 0 and 6.');
        }

        return $precision;
    }

    private function normalizeRoundingMode(mixed $value): string
    {
        if (! is_string($value)) {
            return 'half_up';
        }

        $allowed = ['half_up', 'half_down', 'bankers', 'up', 'down'];

        if (! in_array($value, $allowed, true)) {
            throw new InvalidArgumentException(
                'rounding_mode must be one of: half_up, half_down, bankers, up, down.'
            );
        }

        return $value;
    }

    private function normalizeNonNegativeFloat(mixed $value, string $field): float
    {
        if (! is_numeric($value)) {
            return 0.0;
        }

        $normalized = (float) $value;

        if ($normalized < 0) {
            throw new InvalidArgumentException("{$field} must be greater than or equal to zero.");
        }

        return $normalized;
    }

    /**
     * @return array<int, array{name: string, amount: float}>
     */
    private function normalizeFixedCharges(mixed $charges): array
    {
        if (! is_array($charges)) {
            return [];
        }

        $normalized = [];

        foreach ($charges as $index => $charge) {
            if (! is_array($charge)) {
                throw new InvalidArgumentException("fixed_charges entry at index {$index} must be an object.");
            }

            $name = $charge['name'] ?? "Fixed charge {$index}";
            $amount = $charge['amount'] ?? null;

            if (! is_string($name) || $name === '') {
                throw new InvalidArgumentException("fixed_charges entry at index {$index} must include a non-empty name.");
            }

            if (! is_numeric($amount)) {
                throw new InvalidArgumentException("fixed_charges entry '{$name}' must include a numeric amount.");
            }

            $amountValue = (float) $amount;

            if ($amountValue < 0) {
                throw new InvalidArgumentException("fixed_charges entry '{$name}' amount must be non-negative.");
            }

            $normalized[] = [
                'name' => $name,
                'amount' => $amountValue,
            ];
        }

        return $normalized;
    }

    /**
     * @return array<int, array{name: string, percentage: float}>
     */
    private function normalizeSurcharges(mixed $surcharges): array
    {
        if (! is_array($surcharges)) {
            return [];
        }

        $normalized = [];

        foreach ($surcharges as $index => $surcharge) {
            if (! is_array($surcharge)) {
                throw new InvalidArgumentException("surcharges entry at index {$index} must be an object.");
            }

            $name = $surcharge['name'] ?? "Surcharge {$index}";
            $percentage = $surcharge['percentage'] ?? null;

            if (! is_string($name) || $name === '') {
                throw new InvalidArgumentException("surcharges entry at index {$index} must include a non-empty name.");
            }

            if (! is_numeric($percentage)) {
                throw new InvalidArgumentException("surcharges entry '{$name}' must include a numeric percentage.");
            }

            $percentageValue = (float) $percentage;

            if ($percentageValue < 0) {
                throw new InvalidArgumentException("surcharges entry '{$name}' percentage must be non-negative.");
            }

            $normalized[] = [
                'name' => $name,
                'percentage' => $percentageValue,
            ];
        }

        return $normalized;
    }

    private function roundAmount(float $amount, int $precision, string $mode): float
    {
        $factor = 10 ** $precision;

        return match ($mode) {
            'half_down' => round($amount, $precision, PHP_ROUND_HALF_DOWN),
            'bankers' => round($amount, $precision, PHP_ROUND_HALF_EVEN),
            'up' => $amount >= 0
                ? ceil($amount * $factor) / $factor
                : floor($amount * $factor) / $factor,
            'down' => $amount >= 0
                ? floor($amount * $factor) / $factor
                : ceil($amount * $factor) / $factor,
            default => round($amount, $precision, PHP_ROUND_HALF_UP),
        };
    }
}
