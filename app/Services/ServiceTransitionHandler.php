<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ServiceConfigurationException;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Models\Tenant;
use App\ValueObjects\BillingPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service Transition Handler
 * 
 * Handles tenant move-in/move-out scenarios for property relationships.
 * Generates final meter readings and calculates pro-rated charges.
 * Supports temporary service suspensions.
 * 
 * Requirements: 12.1, 12.2, 12.3, 12.4, 12.5
 * 
 * @package App\Services
 */
final class ServiceTransitionHandler
{
    public function __construct(
        private readonly MeterReadingService $meterReadingService,
        private readonly UniversalBillingCalculator $billingCalculator,
    ) {}

    /**
     * Handle tenant move-out scenario.
     * 
     * Generates final meter readings, calculates pro-rated charges,
     * and closes service accounts.
     *
     * @param Property $property
     * @param Tenant $tenant
     * @param Carbon $moveOutDate
     * @param array $finalReadings Optional final meter readings
     * @return array Move-out summary with final charges
     */
    public function handleMoveOut(
        Property $property,
        Tenant $tenant,
        Carbon $moveOutDate,
        array $finalReadings = []
    ): array {
        return DB::transaction(function () use ($property, $tenant, $moveOutDate, $finalReadings) {
            // Get active service configurations
            $configurations = ServiceConfiguration::where('property_id', $property->id)
                ->where('is_active', true)
                ->effectiveOn($moveOutDate)
                ->get();

            $finalCharges = [];
            $generatedReadings = [];

            foreach ($configurations as $configuration) {
                // Get meters for this configuration
                $meters = Meter::where('property_id', $property->id)
                    ->where('service_configuration_id', $configuration->id)
                    ->get();

                foreach ($meters as $meter) {
                    // Generate or record final reading
                    $reading = $this->generateFinalReading(
                        $meter,
                        $moveOutDate,
                        $finalReadings[$meter->id] ?? null
                    );

                    $generatedReadings[] = $reading;

                    // Calculate pro-rated charges
                    $charges = $this->calculateProRatedCharges(
                        $configuration,
                        $meter,
                        $moveOutDate
                    );

                    $finalCharges[] = [
                        'meter_id' => $meter->id,
                        'meter_serial' => $meter->serial_number,
                        'service_name' => $configuration->utilityService->name,
                        'charges' => $charges,
                        'reading' => $reading,
                    ];
                }

                // Mark configuration as ending
                $configuration->update([
                    'effective_until' => $moveOutDate,
                    'is_active' => false,
                ]);
            }

            // Update tenant assignment
            DB::table('property_tenant')
                ->where('property_id', $property->id)
                ->where('tenant_id', $tenant->id)
                ->whereNull('vacated_at')
                ->update(['vacated_at' => $moveOutDate]);

            // Log activity
            activity()
                ->performedOn($property)
                ->causedBy(auth()->user())
                ->withProperties([
                    'tenant_id' => $tenant->id,
                    'move_out_date' => $moveOutDate->toDateString(),
                    'final_charges' => $finalCharges,
                    'generated_readings' => count($generatedReadings),
                ])
                ->log('tenant_moved_out');

            return [
                'property_id' => $property->id,
                'tenant_id' => $tenant->id,
                'move_out_date' => $moveOutDate->toDateString(),
                'final_charges' => $finalCharges,
                'total_amount' => array_sum(array_column($finalCharges, 'charges')),
                'generated_readings' => $generatedReadings,
            ];
        });
    }

    /**
     * Handle tenant move-in scenario.
     * 
     * Establishes new service accounts and records initial readings.
     *
     * @param Property $property
     * @param Tenant $tenant
     * @param Carbon $moveInDate
     * @param array $initialReadings Optional initial meter readings
     * @return array Move-in summary
     */
    public function handleMoveIn(
        Property $property,
        Tenant $tenant,
        Carbon $moveInDate,
        array $initialReadings = []
    ): array {
        return DB::transaction(function () use ($property, $tenant, $moveInDate, $initialReadings) {
            // Get or create service configurations
            $configurations = ServiceConfiguration::where('property_id', $property->id)
                ->where('is_active', true)
                ->effectiveOn($moveInDate)
                ->get();

            if ($configurations->isEmpty()) {
                throw ServiceConfigurationException::missingRequiredConfiguration(
                    'No active service configurations found for property'
                );
            }

            $generatedReadings = [];

            foreach ($configurations as $configuration) {
                // Update effective_from if needed
                if ($configuration->effective_from < $moveInDate) {
                    $configuration->update(['effective_from' => $moveInDate]);
                }

                // Get meters for this configuration
                $meters = Meter::where('property_id', $property->id)
                    ->where('service_configuration_id', $configuration->id)
                    ->get();

                foreach ($meters as $meter) {
                    // Generate or record initial reading
                    $reading = $this->generateInitialReading(
                        $meter,
                        $moveInDate,
                        $initialReadings[$meter->id] ?? null
                    );

                    $generatedReadings[] = $reading;
                }
            }

            // Create tenant assignment
            DB::table('property_tenant')->insert([
                'property_id' => $property->id,
                'tenant_id' => $tenant->id,
                'assigned_at' => $moveInDate,
                'vacated_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Log activity
            activity()
                ->performedOn($property)
                ->causedBy(auth()->user())
                ->withProperties([
                    'tenant_id' => $tenant->id,
                    'move_in_date' => $moveInDate->toDateString(),
                    'generated_readings' => count($generatedReadings),
                ])
                ->log('tenant_moved_in');

            return [
                'property_id' => $property->id,
                'tenant_id' => $tenant->id,
                'move_in_date' => $moveInDate->toDateString(),
                'generated_readings' => $generatedReadings,
                'active_configurations' => $configurations->count(),
            ];
        });
    }

    /**
     * Suspend service temporarily.
     * 
     * Preserves meter and configuration data while marking service as inactive.
     *
     * @param ServiceConfiguration $configuration
     * @param Carbon $suspensionDate
     * @param string|null $reason
     * @return ServiceConfiguration
     */
    public function suspendService(
        ServiceConfiguration $configuration,
        Carbon $suspensionDate,
        ?string $reason = null
    ): ServiceConfiguration {
        return DB::transaction(function () use ($configuration, $suspensionDate, $reason) {
            // Generate final readings before suspension
            $meters = Meter::where('service_configuration_id', $configuration->id)->get();
            
            foreach ($meters as $meter) {
                $this->generateFinalReading($meter, $suspensionDate);
            }

            // Mark configuration as suspended
            $configuration->update([
                'is_active' => false,
                'effective_until' => $suspensionDate,
            ]);

            // Log activity
            activity()
                ->performedOn($configuration)
                ->causedBy(auth()->user())
                ->withProperties([
                    'suspension_date' => $suspensionDate->toDateString(),
                    'reason' => $reason,
                    'meters_count' => $meters->count(),
                ])
                ->log('service_suspended');

            return $configuration->fresh();
        });
    }

    /**
     * Reactivate suspended service.
     *
     * @param ServiceConfiguration $configuration
     * @param Carbon $reactivationDate
     * @return ServiceConfiguration
     */
    public function reactivateService(
        ServiceConfiguration $configuration,
        Carbon $reactivationDate
    ): ServiceConfiguration {
        return DB::transaction(function () use ($configuration, $reactivationDate) {
            // Generate initial readings for reactivation
            $meters = Meter::where('service_configuration_id', $configuration->id)->get();
            
            foreach ($meters as $meter) {
                $this->generateInitialReading($meter, $reactivationDate);
            }

            // Reactivate configuration
            $configuration->update([
                'is_active' => true,
                'effective_from' => $reactivationDate,
                'effective_until' => null,
            ]);

            // Log activity
            activity()
                ->performedOn($configuration)
                ->causedBy(auth()->user())
                ->withProperties([
                    'reactivation_date' => $reactivationDate->toDateString(),
                    'meters_count' => $meters->count(),
                ])
                ->log('service_reactivated');

            return $configuration->fresh();
        });
    }

    /**
     * Generate final meter reading.
     *
     * @param Meter $meter
     * @param Carbon $readingDate
     * @param float|array|null $readingValue
     * @return MeterReading
     */
    private function generateFinalReading(
        Meter $meter,
        Carbon $readingDate,
        float|array|null $readingValue = null
    ): MeterReading {
        // If no reading provided, use last reading or estimate
        if ($readingValue === null) {
            $lastReading = $this->meterReadingService->getLatestReading($meter);
            
            if ($lastReading) {
                // Use last reading value (no consumption since last reading)
                $readingValue = $lastReading->value;
            } else {
                // No previous reading, start at 0
                $readingValue = 0;
            }
        }

        // Create reading
        $reading = MeterReading::create([
            'tenant_id' => $meter->tenant_id,
            'meter_id' => $meter->id,
            'reading_date' => $readingDate,
            'value' => is_array($readingValue) ? ($readingValue['value'] ?? 0) : $readingValue,
            'reading_values' => is_array($readingValue) ? $readingValue : null,
            'entered_by' => auth()->id(),
            'is_estimated' => $readingValue === null,
            'input_method' => \App\Enums\InputMethod::MANUAL,
            'validation_status' => \App\Enums\ValidationStatus::VALIDATED,
        ]);

        return $reading;
    }

    /**
     * Generate initial meter reading.
     *
     * @param Meter $meter
     * @param Carbon $readingDate
     * @param float|array|null $readingValue
     * @return MeterReading
     */
    private function generateInitialReading(
        Meter $meter,
        Carbon $readingDate,
        float|array|null $readingValue = null
    ): MeterReading {
        // If no reading provided, use last reading or start at 0
        if ($readingValue === null) {
            $lastReading = $this->meterReadingService->getLatestReading($meter);
            $readingValue = $lastReading?->value ?? 0;
        }

        // Create reading
        $reading = MeterReading::create([
            'tenant_id' => $meter->tenant_id,
            'meter_id' => $meter->id,
            'reading_date' => $readingDate,
            'value' => is_array($readingValue) ? ($readingValue['value'] ?? 0) : $readingValue,
            'reading_values' => is_array($readingValue) ? $readingValue : null,
            'entered_by' => auth()->id(),
            'is_estimated' => false,
            'input_method' => \App\Enums\InputMethod::MANUAL,
            'validation_status' => \App\Enums\ValidationStatus::VALIDATED,
        ]);

        return $reading;
    }

    /**
     * Calculate pro-rated charges for partial billing period.
     *
     * @param ServiceConfiguration $configuration
     * @param Meter $meter
     * @param Carbon $endDate
     * @return float
     */
    private function calculateProRatedCharges(
        ServiceConfiguration $configuration,
        Meter $meter,
        Carbon $endDate
    ): float {
        // Get billing period start (last billing date or configuration start)
        $startDate = $configuration->effective_from;
        
        // Get last reading
        $lastReading = $this->meterReadingService->getLatestReading($meter);
        if ($lastReading && $lastReading->reading_date > $startDate) {
            $startDate = $lastReading->reading_date;
        }

        // Calculate consumption for the period
        $currentReading = $this->meterReadingService->getLatestReading($meter);
        $previousReading = $this->meterReadingService->getPreviousReading(
            $meter,
            null,
            $currentReading->reading_date->toDateString()
        );

        $consumption = $currentReading->value - ($previousReading?->value ?? 0);

        // Calculate charges using billing calculator
        $billingPeriod = new BillingPeriod($startDate, $endDate);
        
        // Use billing calculator to compute charges
        $charges = $this->billingCalculator->calculateCharges(
            $configuration,
            $consumption,
            $billingPeriod
        );

        return $charges;
    }
}
