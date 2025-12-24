<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\ValidationStatus;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Services\UniversalBillingCalculator;
use App\ValueObjects\BillingOptions;
use App\ValueObjects\BillingPeriod;

/**
 * Processes universal service charges.
 * 
 * Handles billing for universal utility services
 * using the universal billing calculator.
 * 
 * @package App\Services\Billing
 */
final readonly class UniversalServiceProcessor
{
    public function __construct(
        private UniversalBillingCalculator $universalCalculator,
    ) {}

    /**
     * Process universal service charges for a property.
     */
    public function processUniversalServices(
        Property $property,
        BillingPeriod $billingPeriod,
        BillingOptions $options
    ): array {
        $totalAmount = 0.0;
        $items = [];

        // Get service configurations for this property with eager loading
        $serviceConfigurations = ServiceConfiguration::where('property_id', $property->id)
            ->with(['utilityService', 'meters'])
            ->get();

        foreach ($serviceConfigurations as $serviceConfig) {
            $serviceResult = $this->processServiceConfiguration(
                $serviceConfig,
                $billingPeriod,
                $options
            );
            
            $totalAmount += $serviceResult['amount'];
            $items = array_merge($items, $serviceResult['items']);
        }

        return [
            'amount' => $totalAmount,
            'items' => $items,
        ];
    }

    private function processServiceConfiguration(
        ServiceConfiguration $serviceConfig,
        BillingPeriod $billingPeriod,
        BillingOptions $options
    ): array {
        $totalAmount = 0.0;
        $items = [];

        // Get meters associated with this service configuration
        // Task 12.1: Only process VALIDATED readings for billing (Truth-but-Verify flow)
        $meters = Meter::where('service_configuration_id', $serviceConfig->id)
            ->with(['readings' => function ($query) use ($billingPeriod) {
                $query->whereBetween('reading_date', [
                    $billingPeriod->getStartDate(),
                    $billingPeriod->getEndDate()
                ])
                ->where('validation_status', ValidationStatus::VALIDATED)
                ->orderBy('reading_date');
            }])
            ->get();

        foreach ($meters as $meter) {
            // Get readings for the billing period (already eager loaded)
            $readings = $meter->readings;

            if ($readings->isEmpty()) {
                continue; // Skip if no readings available
            }

            // Calculate charges using universal calculator
            $calculationResult = $this->universalCalculator->calculateCharges(
                $serviceConfig,
                $readings,
                $billingPeriod
            );

            $amount = $calculationResult->getTotalAmount();
            $totalAmount += $amount;
            
            $items[] = [
                'meter_id' => $meter->id,
                'service_configuration_id' => $serviceConfig->id,
                'service_type' => $serviceConfig->utilityService->name,
                'description' => "{$serviceConfig->utilityService->name} charges for {$billingPeriod->getLabel()}",
                'quantity' => 1.0,
                'unit_price' => $amount,
                'total' => $amount,
                'calculation_details' => $calculationResult->toArray(),
            ];
        }

        return [
            'amount' => $totalAmount,
            'items' => $items,
        ];
    }
}