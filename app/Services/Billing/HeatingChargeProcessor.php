<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\MeterType;
use App\Models\MeterReading;
use App\Models\Property;
use App\Services\HeatingCalculatorService;
use App\ValueObjects\BillingPeriod;
use Illuminate\Support\Collection;

/**
 * Processes heating charges using existing heating calculator.
 * 
 * Integrates with the existing heating calculation system
 * while providing a clean interface for the billing engine.
 * 
 * @package App\Services\Billing
 */
final readonly class HeatingChargeProcessor
{
    public function __construct(
        private HeatingCalculatorService $heatingCalculator,
    ) {}

    /**
     * Process heating charges for a property.
     */
    public function processHeatingCharges(
        Property $property,
        Collection $meters,
        BillingPeriod $billingPeriod
    ): array {
        $totalAmount = 0.0;
        $items = [];

        $heatingMeters = $meters->where('type', MeterType::HEATING);
        
        if ($heatingMeters->isEmpty()) {
            return ['amount' => 0.0, 'items' => []];
        }

        foreach ($heatingMeters as $meter) {
            // Get latest reading for the billing period
            $reading = $this->getLatestReadingForPeriod($meter->id, $billingPeriod);

            if (!$reading) {
                continue; // Skip if no reading available
            }

            // Calculate heating charges using existing calculator
            $heatingResult = $this->heatingCalculator->calculateHeatingCost(
                $property,
                $reading,
                $billingPeriod->getStartDate()
            );

            $amount = $heatingResult['amount'];
            $totalAmount += $amount;
            
            $items[] = [
                'meter_id' => $meter->id,
                'service_type' => 'heating',
                'description' => "Heating charges for {$billingPeriod->getLabel()}",
                'quantity' => 1.0,
                'unit_price' => $amount,
                'total' => $amount,
                'calculation_details' => $heatingResult['details'],
            ];
        }

        return [
            'amount' => $totalAmount,
            'items' => $items,
        ];
    }

    private function getLatestReadingForPeriod(int $meterId, BillingPeriod $billingPeriod): ?MeterReading
    {
        return MeterReading::where('meter_id', $meterId)
            ->where('reading_date', '>=', $billingPeriod->getStartDate())
            ->where('reading_date', '<=', $billingPeriod->getEndDate())
            ->latest('reading_date')
            ->first();
    }
}