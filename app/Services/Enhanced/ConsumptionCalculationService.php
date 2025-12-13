<?php

declare(strict_types=1);

namespace App\Services\Enhanced;

use App\Models\Property;
use App\Models\MeterReading;
use App\Services\ServiceResponse;
use App\Services\MeterReadingService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Consumption Calculation Service
 * 
 * Specialized service for calculating utility consumption with:
 * - Multi-meter property support
 * - Zone-based calculations
 * - Seasonal adjustments
 * - Estimation handling
 * - Historical pattern analysis
 * 
 * @package App\Services\Enhanced
 */
final class ConsumptionCalculationService extends BaseService
{
    public function __construct(
        private readonly MeterReadingService $meterReadingService
    ) {
        parent::__construct();
    }

    /**
     * Calculate consumption for all services in a property for a billing period.
     *
     * @param Property $property
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @return ServiceResponse<array>
     */
    public function calculatePropertyConsumption(
        Property $property,
        Carbon $periodStart,
        Carbon $periodEnd
    ): ServiceResponse {
        try {
            $this->authorize('view', $property);
            $this->validateTenantOwnership($property);

            return $this->withMetrics('calculate_property_consumption', function () use ($property, $periodStart, $periodEnd) {
                // Get all meters for the property
                $meters = $property->meters()
                    ->with(['serviceConfiguration.utilityService'])
                    ->where('is_active', true)
                    ->get();

                if ($meters->isEmpty()) {
                    return $this->error('No active meters found for property');
                }

                $consumptionData = [];

                foreach ($meters as $meter) {
                    $meterConsumption = $this->calculateMeterConsumption(
                        $meter,
                        $periodStart,
                        $periodEnd
                    );

                    if ($meterConsumption->success) {
                        $consumptionData[] = array_merge($meterConsumption->data, [
                            'meter_id' => $meter->id,
                            'service_configuration' => $meter->serviceConfiguration,
                        ]);
                    }
                }

                // Group by service configuration for shared services
                $groupedConsumption = $this->groupConsumptionByService($consumptionData);

                $this->log('info', 'Property consumption calculated', [
                    'property_id' => $property->id,
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'meter_count' => $meters->count(),
                    'service_count' => count($groupedConsumption),
                ]);

                return $this->success($groupedConsumption, 'Property consumption calculated successfully');
            });
        } catch (\Exception $e) {
            $this->handleException($e, [
                'operation' => 'calculate_property_consumption',
                'property_id' => $property->id,
            ]);

            return $this->error('Failed to calculate property consumption: ' . $e->getMessage());
        }
    }

    /**
     * Calculate consumption for a single meter.
     *
     * @param \App\Models\Meter $meter
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @return ServiceResponse<array>
     */
    public function calculateMeterConsumption(
        \App\Models\Meter $meter,
        Carbon $periodStart,
        Carbon $periodEnd
    ): ServiceResponse {
        try {
            return $this->withMetrics('calculate_meter_consumption', function () use ($meter, $periodStart, $periodEnd) {
                // Get readings for the period
                $readings = $this->getMeterReadingsForPeriod($meter, $periodStart, $periodEnd);

                if ($readings->isEmpty()) {
                    return $this->error('No readings found for meter in period');
                }

                // Calculate consumption based on meter type
                if ($meter->supports_zones) {
                    $consumption = $this->calculateZonedConsumption($readings, $periodStart, $periodEnd);
                } else {
                    $consumption = $this->calculateSimpleConsumption($readings, $periodStart, $periodEnd);
                }

                // Apply seasonal adjustments if configured
                $adjustedConsumption = $this->applySeasonalAdjustments(
                    $consumption,
                    $meter->serviceConfiguration,
                    $periodStart,
                    $periodEnd
                );

                // Validate consumption against historical patterns
                $validation = $this->validateConsumptionPattern($meter, $adjustedConsumption, $periodStart);

                return $this->success([
                    'consumption' => $adjustedConsumption,
                    'raw_consumption' => $consumption,
                    'readings_count' => $readings->count(),
                    'validation' => $validation,
                    'calculation_method' => $meter->supports_zones ? 'zoned' : 'simple',
                ], 'Meter consumption calculated successfully');
            });
        } catch (\Exception $e) {
            $this->handleException($e, [
                'operation' => 'calculate_meter_consumption',
                'meter_id' => $meter->id,
            ]);

            return $this->error('Failed to calculate meter consumption: ' . $e->getMessage());
        }
    }

    /**
     * Get consumption history for analysis and reporting.
     *
     * @param \App\Models\Meter $meter
     * @param int $months
     * @return ServiceResponse<Collection>
     */
    public function getConsumptionHistory(\App\Models\Meter $meter, int $months = 12): ServiceResponse
    {
        try {
            $this->authorize('view', $meter);
            $this->validateTenantOwnership($meter);

            $startDate = now()->subMonths($months)->startOfMonth();
            $history = collect();

            // Calculate consumption for each month
            for ($i = 0; $i < $months; $i++) {
                $periodStart = $startDate->copy()->addMonths($i);
                $periodEnd = $periodStart->copy()->endOfMonth();

                if ($periodEnd->isFuture()) {
                    break;
                }

                $consumptionResult = $this->calculateMeterConsumption($meter, $periodStart, $periodEnd);

                if ($consumptionResult->success) {
                    $history->push([
                        'period' => $periodStart->format('Y-m'),
                        'period_start' => $periodStart->toDateString(),
                        'period_end' => $periodEnd->toDateString(),
                        'consumption' => $consumptionResult->data['consumption'],
                        'readings_count' => $consumptionResult->data['readings_count'],
                    ]);
                }
            }

            return $this->success($history, 'Consumption history retrieved successfully');
        } catch (\Exception $e) {
            $this->handleException($e, [
                'operation' => 'get_consumption_history',
                'meter_id' => $meter->id,
            ]);

            return $this->error('Failed to retrieve consumption history: ' . $e->getMessage());
        }
    }

    /**
     * Get meter readings for a specific period.
     */
    private function getMeterReadingsForPeriod(
        \App\Models\Meter $meter,
        Carbon $periodStart,
        Carbon $periodEnd
    ): Collection {
        return MeterReading::where('meter_id', $meter->id)
            ->whereBetween('reading_date', [$periodStart, $periodEnd])
            ->where('validation_status', 'validated')
            ->orderBy('reading_date')
            ->orderBy('zone')
            ->get();
    }

    /**
     * Calculate consumption for zoned meters.
     */
    private function calculateZonedConsumption(
        Collection $readings,
        Carbon $periodStart,
        Carbon $periodEnd
    ): float {
        $totalConsumption = 0;

        // Group readings by zone
        $readingsByZone = $readings->groupBy('zone');

        foreach ($readingsByZone as $zone => $zoneReadings) {
            $zoneConsumption = $this->calculateZoneConsumption($zoneReadings, $periodStart, $periodEnd);
            $totalConsumption += $zoneConsumption;
        }

        return $totalConsumption;
    }

    /**
     * Calculate consumption for a specific zone.
     */
    private function calculateZoneConsumption(Collection $readings, Carbon $periodStart, Carbon $periodEnd): float
    {
        if ($readings->count() < 2) {
            return 0; // Need at least 2 readings to calculate consumption
        }

        $sortedReadings = $readings->sortBy('reading_date');
        $firstReading = $sortedReadings->first();
        $lastReading = $sortedReadings->last();

        // Handle different reading structures
        if (isset($firstReading->reading_values) && is_array($firstReading->reading_values)) {
            return $this->calculateMultiValueConsumption($sortedReadings);
        }

        // Simple consumption calculation
        return max(0, $lastReading->value - $firstReading->value);
    }

    /**
     * Calculate consumption for simple (non-zoned) meters.
     */
    private function calculateSimpleConsumption(
        Collection $readings,
        Carbon $periodStart,
        Carbon $periodEnd
    ): float {
        if ($readings->count() < 2) {
            return 0;
        }

        $sortedReadings = $readings->sortBy('reading_date');
        $firstReading = $sortedReadings->first();
        $lastReading = $sortedReadings->last();

        return max(0, $lastReading->value - $firstReading->value);
    }

    /**
     * Calculate consumption for multi-value readings.
     */
    private function calculateMultiValueConsumption(Collection $readings): float
    {
        $totalConsumption = 0;
        $previousReading = null;

        foreach ($readings as $reading) {
            if ($previousReading) {
                $consumption = $this->calculateReadingDifference($previousReading, $reading);
                $totalConsumption += $consumption;
            }
            $previousReading = $reading;
        }

        return $totalConsumption;
    }

    /**
     * Calculate difference between two readings.
     */
    private function calculateReadingDifference(MeterReading $previous, MeterReading $current): float
    {
        if (isset($current->reading_values) && is_array($current->reading_values)) {
            $consumption = 0;
            foreach ($current->reading_values as $key => $value) {
                if (isset($previous->reading_values[$key]) && is_numeric($value) && is_numeric($previous->reading_values[$key])) {
                    $consumption += max(0, $value - $previous->reading_values[$key]);
                }
            }
            return $consumption;
        }

        return max(0, $current->value - $previous->value);
    }

    /**
     * Apply seasonal adjustments to consumption.
     */
    private function applySeasonalAdjustments(
        float $consumption,
        ?\App\Models\ServiceConfiguration $serviceConfig,
        Carbon $periodStart,
        Carbon $periodEnd
    ): float {
        if (!$serviceConfig || !$serviceConfig->seasonal_adjustments) {
            return $consumption;
        }

        $adjustments = $serviceConfig->seasonal_adjustments;
        $season = $this->determineSeason($periodStart, $periodEnd);

        if (isset($adjustments[$season]['multiplier'])) {
            $multiplier = (float) $adjustments[$season]['multiplier'];
            return $consumption * $multiplier;
        }

        return $consumption;
    }

    /**
     * Determine season for the billing period.
     */
    private function determineSeason(Carbon $periodStart, Carbon $periodEnd): string
    {
        $midPoint = $periodStart->copy()->addDays($periodStart->diffInDays($periodEnd) / 2);
        $month = $midPoint->month;

        return match (true) {
            in_array($month, [12, 1, 2]) => 'winter',
            in_array($month, [3, 4, 5]) => 'spring',
            in_array($month, [6, 7, 8]) => 'summer',
            in_array($month, [9, 10, 11]) => 'autumn',
            default => 'unknown',
        };
    }

    /**
     * Validate consumption against historical patterns.
     */
    private function validateConsumptionPattern(
        \App\Models\Meter $meter,
        float $consumption,
        Carbon $periodStart
    ): array {
        try {
            // Get historical consumption for the same period in previous years
            $historicalReadings = MeterReading::where('meter_id', $meter->id)
                ->whereMonth('reading_date', $periodStart->month)
                ->where('reading_date', '<', $periodStart)
                ->where('validation_status', 'validated')
                ->orderBy('reading_date', 'desc')
                ->limit(12) // Last 12 occurrences of this month
                ->get();

            if ($historicalReadings->count() < 3) {
                return [
                    'status' => 'insufficient_data',
                    'message' => 'Insufficient historical data for validation',
                ];
            }

            // Calculate historical average (simplified)
            $historicalAverage = $historicalReadings->avg('value');
            $variance = abs($consumption - $historicalAverage) / $historicalAverage;

            if ($variance > 0.5) { // 50% variance threshold
                return [
                    'status' => 'high_variance',
                    'message' => 'Consumption varies significantly from historical average',
                    'variance_percent' => round($variance * 100, 2),
                    'historical_average' => $historicalAverage,
                ];
            }

            return [
                'status' => 'normal',
                'message' => 'Consumption within expected range',
                'variance_percent' => round($variance * 100, 2),
                'historical_average' => $historicalAverage,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'validation_error',
                'message' => 'Could not validate consumption pattern',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Group consumption data by service configuration.
     */
    private function groupConsumptionByService(array $consumptionData): array
    {
        $grouped = [];

        foreach ($consumptionData as $data) {
            $serviceId = $data['service_configuration']->id;

            if (!isset($grouped[$serviceId])) {
                $grouped[$serviceId] = [
                    'service_configuration' => $data['service_configuration'],
                    'consumption' => 0,
                    'meter_ids' => [],
                    'readings_count' => 0,
                ];
            }

            $grouped[$serviceId]['consumption'] += $data['consumption'];
            $grouped[$serviceId]['meter_ids'][] = $data['meter_id'];
            $grouped[$serviceId]['readings_count'] += $data['readings_count'];
        }

        return array_values($grouped);
    }
}