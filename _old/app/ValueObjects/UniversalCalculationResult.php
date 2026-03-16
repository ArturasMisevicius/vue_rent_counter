<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Value object representing a universal billing calculation result.
 * 
 * Contains comprehensive billing information including base amounts,
 * adjustments, consumption charges, fixed fees, and audit trail data.
 */
final readonly class UniversalCalculationResult
{
    /**
     * @param float $totalAmount Final total amount to be charged
     * @param float $baseAmount Base amount before adjustments
     * @param array $adjustments Array of adjustments applied to base amount
     * @param float $consumptionAmount Amount charged for consumption
     * @param float $fixedAmount Fixed fee component
     * @param array $tariffSnapshot Snapshot of tariff/configuration data for audit trail
     * @param array $calculationDetails Detailed breakdown of calculation steps
     */
    public function __construct(
        public float $totalAmount,
        public float $baseAmount,
        public array $adjustments = [],
        public float $consumptionAmount = 0.0,
        public float $fixedAmount = 0.0,
        public array $tariffSnapshot = [],
        public array $calculationDetails = [],
    ) {}

    /**
     * Create a simple result with just total amount.
     */
    public static function simple(float $totalAmount): self
    {
        return new self(
            totalAmount: $totalAmount,
            baseAmount: $totalAmount,
        );
    }

    /**
     * Create a consumption-based result.
     */
    public static function consumption(
        float $consumptionAmount,
        array $tariffSnapshot = [],
        array $calculationDetails = []
    ): self {
        return new self(
            totalAmount: $consumptionAmount,
            baseAmount: $consumptionAmount,
            consumptionAmount: $consumptionAmount,
            tariffSnapshot: $tariffSnapshot,
            calculationDetails: $calculationDetails,
        );
    }

    /**
     * Create a fixed fee result.
     */
    public static function fixed(
        float $fixedAmount,
        array $adjustments = [],
        array $tariffSnapshot = [],
        array $calculationDetails = []
    ): self {
        $totalAmount = $fixedAmount + array_sum(array_column($adjustments, 'amount'));
        
        return new self(
            totalAmount: $totalAmount,
            baseAmount: $fixedAmount,
            adjustments: $adjustments,
            fixedAmount: $fixedAmount,
            tariffSnapshot: $tariffSnapshot,
            calculationDetails: $calculationDetails,
        );
    }

    /**
     * Create a hybrid result (fixed + consumption).
     */
    public static function hybrid(
        float $fixedAmount,
        float $consumptionAmount,
        array $adjustments = [],
        array $tariffSnapshot = [],
        array $calculationDetails = []
    ): self {
        $baseAmount = $fixedAmount + $consumptionAmount;
        $totalAmount = $baseAmount + array_sum(array_column($adjustments, 'amount'));
        
        return new self(
            totalAmount: $totalAmount,
            baseAmount: $baseAmount,
            adjustments: $adjustments,
            consumptionAmount: $consumptionAmount,
            fixedAmount: $fixedAmount,
            tariffSnapshot: $tariffSnapshot,
            calculationDetails: $calculationDetails,
        );
    }

    /**
     * Get the total adjustment amount.
     */
    public function getTotalAdjustments(): float
    {
        return array_sum(array_column($this->adjustments, 'amount'));
    }

    /**
     * Check if there are any adjustments.
     */
    public function hasAdjustments(): bool
    {
        return !empty($this->adjustments);
    }

    /**
     * Get adjustments of a specific type.
     */
    public function getAdjustmentsByType(string $type): array
    {
        return array_filter(
            $this->adjustments,
            fn($adjustment) => ($adjustment['type'] ?? '') === $type
        );
    }

    /**
     * Check if the result has consumption charges.
     */
    public function hasConsumptionCharges(): bool
    {
        return $this->consumptionAmount > 0;
    }

    /**
     * Check if the result has fixed charges.
     */
    public function hasFixedCharges(): bool
    {
        return $this->fixedAmount > 0;
    }

    /**
     * Get calculation detail by key.
     */
    public function getCalculationDetail(string $key, mixed $default = null): mixed
    {
        return $this->calculationDetails[$key] ?? $default;
    }

    /**
     * Check if calculation detail exists.
     */
    public function hasCalculationDetail(string $key): bool
    {
        return array_key_exists($key, $this->calculationDetails);
    }

    /**
     * Get tariff snapshot value by key.
     */
    public function getTariffSnapshotValue(string $key, mixed $default = null): mixed
    {
        return $this->tariffSnapshot[$key] ?? $default;
    }

    /**
     * Check if tariff snapshot has a key.
     */
    public function hasTariffSnapshotValue(string $key): bool
    {
        return array_key_exists($key, $this->tariffSnapshot);
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'total_amount' => $this->totalAmount,
            'base_amount' => $this->baseAmount,
            'adjustments' => $this->adjustments,
            'consumption_amount' => $this->consumptionAmount,
            'fixed_amount' => $this->fixedAmount,
            'tariff_snapshot' => $this->tariffSnapshot,
            'calculation_details' => $this->calculationDetails,
        ];
    }

    /**
     * Get a formatted breakdown of the calculation.
     */
    public function getBreakdown(): array
    {
        $breakdown = [];
        
        if ($this->hasFixedCharges()) {
            $breakdown['Fixed Charges'] = $this->fixedAmount;
        }
        
        if ($this->hasConsumptionCharges()) {
            $breakdown['Consumption Charges'] = $this->consumptionAmount;
        }
        
        foreach ($this->adjustments as $adjustment) {
            $description = $adjustment['description'] ?? $adjustment['type'] ?? 'Adjustment';
            $breakdown[$description] = $adjustment['amount'] ?? 0;
        }
        
        $breakdown['Total'] = $this->totalAmount;
        
        return $breakdown;
    }

    /**
     * Check if the calculation result is zero.
     */
    public function isZero(): bool
    {
        return abs($this->totalAmount) < 0.01;
    }

    /**
     * Check if the calculation result is positive.
     */
    public function isPositive(): bool
    {
        return $this->totalAmount > 0.01;
    }

    /**
     * Check if the calculation result is negative.
     */
    public function isNegative(): bool
    {
        return $this->totalAmount < -0.01;
    }

    /**
     * Round all monetary values to specified precision.
     */
    public function withPrecision(int $precision = 2): self
    {
        $roundedAdjustments = array_map(function ($adjustment) use ($precision) {
            if (isset($adjustment['amount'])) {
                $adjustment['amount'] = round($adjustment['amount'], $precision);
            }
            return $adjustment;
        }, $this->adjustments);
        
        return new self(
            totalAmount: round($this->totalAmount, $precision),
            baseAmount: round($this->baseAmount, $precision),
            adjustments: $roundedAdjustments,
            consumptionAmount: round($this->consumptionAmount, $precision),
            fixedAmount: round($this->fixedAmount, $precision),
            tariffSnapshot: $this->tariffSnapshot,
            calculationDetails: $this->calculationDetails,
        );
    }
}