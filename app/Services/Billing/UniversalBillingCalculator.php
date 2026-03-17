<?php

declare(strict_types=1);

namespace App\Services\Billing;

final class UniversalBillingCalculator
{
    public function money(string|int|float $value): string
    {
        return $this->round($value, 2);
    }

    public function quantity(string|int|float $value): string
    {
        return $this->round($value, 3);
    }

    public function rate(string|int|float $value): string
    {
        return $this->round($value, 4);
    }

    public function add(string|int|float $left, string|int|float $right, int $scale = 6): string
    {
        return bcadd($this->normalize($left, $scale), $this->normalize($right, $scale), $scale);
    }

    public function subtract(string|int|float $left, string|int|float $right, int $scale = 6): string
    {
        return bcsub($this->normalize($left, $scale), $this->normalize($right, $scale), $scale);
    }

    public function multiply(string|int|float $left, string|int|float $right, int $scale = 6): string
    {
        return bcmul($this->normalize($left, $scale), $this->normalize($right, $scale), $scale);
    }

    public function divide(string|int|float $left, string|int|float $right, int $scale = 6): string
    {
        if ($this->compare($right, '0', $scale) === 0) {
            return bcadd('0', '0', $scale);
        }

        return bcdiv($this->normalize($left, $scale), $this->normalize($right, $scale), $scale);
    }

    public function compare(string|int|float $left, string|int|float $right, int $scale = 2): int
    {
        return bccomp($this->normalize($left, $scale), $this->normalize($right, $scale), $scale);
    }

    /**
     * @param  array<int, string|int|float>  $values
     */
    public function sum(array $values, int $scale = 6): string
    {
        return array_reduce(
            $values,
            fn (string $carry, string|int|float $value): string => $this->add($carry, $value, $scale),
            bcadd('0', '0', $scale),
        );
    }

    public function calculateFlatRateCharge(
        string|int|float $quantity,
        string|int|float $unitRate,
        string|int|float $baseFee = '0',
    ): string {
        $usageTotal = $this->multiply($quantity, $unitRate, 6);

        return $this->money($this->add($usageTotal, $baseFee, 6));
    }

    /**
     * @param  array<string, string|int|float>  $zoneConsumptions
     * @param  array<int, array{id?: string, rate?: string|int|float}>  $zones
     */
    public function calculateTimeOfUseCharge(
        array $zoneConsumptions,
        array $zones,
        string|int|float $baseFee = '0',
    ): string {
        $total = bcadd('0', '0', 6);

        foreach ($zones as $zone) {
            $zoneId = (string) ($zone['id'] ?? '');

            if ($zoneId === '' || ! array_key_exists($zoneId, $zoneConsumptions)) {
                continue;
            }

            $zoneRate = $zone['rate'] ?? 0;
            $zoneTotal = $this->multiply($zoneConsumptions[$zoneId], $zoneRate, 6);
            $total = $this->add($total, $zoneTotal, 6);
        }

        return $this->money($this->add($total, $baseFee, 6));
    }

    public function round(string|int|float $value, int $scale = 2): string
    {
        $normalized = $this->normalize($value, $scale + 2);
        $negative = str_starts_with($normalized, '-');
        $absolute = $negative ? substr($normalized, 1) : $normalized;

        if (! str_contains($absolute, '.')) {
            $absolute .= '.';
        }

        [$integer, $fraction] = array_pad(explode('.', $absolute, 2), 2, '');
        $fraction = str_pad($fraction, $scale + 1, '0');
        $nextDigit = (int) ($fraction[$scale] ?? '0');
        $truncatedFraction = $scale > 0 ? substr($fraction, 0, $scale) : '';
        $rounded = $scale > 0
            ? $integer.'.'.str_pad($truncatedFraction, $scale, '0')
            : $integer;

        if ($nextDigit >= 5) {
            $increment = $scale > 0
                ? '0.'.str_repeat('0', max(0, $scale - 1)).'1'
                : '1';

            $rounded = bcadd($rounded, $increment, $scale);
        } else {
            $rounded = bcadd($rounded, '0', $scale);
        }

        if ($negative && bccomp($rounded, '0', $scale) !== 0) {
            return bcmul($rounded, '-1', $scale);
        }

        return $rounded;
    }

    private function normalize(string|int|float $value, int $scale): string
    {
        if (is_int($value)) {
            return bcadd((string) $value, '0', $scale);
        }

        if (is_float($value)) {
            return bcadd(sprintf("%.{$scale}F", $value), '0', $scale);
        }

        $resolved = trim($value);

        return bcadd($resolved !== '' ? $resolved : '0', '0', $scale);
    }
}
