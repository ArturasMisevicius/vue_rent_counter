<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMeterReadingRequest;
use App\Models\Meter;
use App\Models\MeterReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MeterApiController extends Controller
{
    /**
     * Get the last reading for a meter with average consumption for anomaly detection.
     * 
     * OPTIMIZED: Single query with conditional aggregation instead of separate zone queries
     * Returns average_consumption based on last 6 readings for anomaly detection UI
     */
    public function lastReading(Meter $meter): JsonResponse
    {
        $cacheKey = "meter:last_reading:{$meter->id}";
        
        $result = Cache::remember($cacheKey, 300, function () use ($meter) {
            if ($meter->supports_zones) {
                // Single query with conditional aggregation for zone-based meters
                $readings = $meter->readings()
                    ->select(['reading_date', 'value', 'zone'])
                    ->whereIn('zone', ['day', 'night'])
                    ->where('reading_date', function ($query) use ($meter) {
                        $query->select(DB::raw('MAX(reading_date)'))
                            ->from('meter_readings')
                            ->where('meter_id', $meter->id)
                            ->whereIn('zone', ['day', 'night']);
                    })
                    ->get()
                    ->keyBy('zone');
                
                if ($readings->isEmpty()) {
                    return null;
                }
                
                $dayReading = $readings->get('day');
                $nightReading = $readings->get('night');
                
                // Calculate average consumption for zone-based meters
                $averageConsumption = $this->calculateAverageConsumption($meter, true);
                
                return [
                    'date' => $dayReading?->reading_date->format('Y-m-d') ?? $nightReading?->reading_date->format('Y-m-d'),
                    'day_value' => $dayReading?->value,
                    'night_value' => $nightReading?->value,
                    'value' => ($dayReading?->value ?? 0) + ($nightReading?->value ?? 0),
                    'average_consumption' => $averageConsumption,
                ];
            }
            
            // Single-zone meters - optimized with selective columns
            $lastReading = $meter->readings()
                ->select(['id', 'value', 'reading_date', 'zone'])
                ->latest('reading_date')
                ->first();
            
            if (!$lastReading) {
                return null;
            }
            
            // Calculate average consumption for single-zone meters
            $averageConsumption = $this->calculateAverageConsumption($meter, false);
            
            return [
                'id' => $lastReading->id,
                'value' => $lastReading->value,
                'date' => $lastReading->reading_date->format('Y-m-d'),
                'zone' => $lastReading->zone,
                'average_consumption' => $averageConsumption,
            ];
        });
        
        if ($result === null) {
            return response()->json(null, 404);
        }
        
        return response()->json($result);
    }

    /**
     * Calculate average consumption based on last N readings.
     * 
     * For anomaly detection: compares current consumption against historical average.
     * Uses last 6 readings (or available readings if fewer) to calculate average.
     *
     * @param Meter $meter
     * @param bool $supportsZones
     * @param int $readingsCount Number of readings to use for average calculation
     * @return float|null Average consumption or null if insufficient data
     */
    private function calculateAverageConsumption(Meter $meter, bool $supportsZones, int $readingsCount = 6): ?float
    {
        if ($supportsZones) {
            // For zone-based meters, get combined day+night consumption
            $readings = $meter->readings()
                ->select(['reading_date', 'value', 'zone'])
                ->whereIn('zone', ['day', 'night'])
                ->orderBy('reading_date', 'desc')
                ->limit($readingsCount * 2) // Get enough for both zones
                ->get()
                ->groupBy('reading_date');
            
            if ($readings->count() < 2) {
                return null;
            }
            
            $consumptions = [];
            $previousTotal = null;
            
            foreach ($readings->sortKeys()->take($readingsCount + 1) as $date => $zoneReadings) {
                $currentTotal = $zoneReadings->sum('value');
                
                if ($previousTotal !== null) {
                    $consumptions[] = $currentTotal - $previousTotal;
                }
                
                $previousTotal = $currentTotal;
            }
            
            if (empty($consumptions)) {
                return null;
            }
            
            return array_sum($consumptions) / count($consumptions);
        }
        
        // Single-zone meters
        $readings = $meter->readings()
            ->select(['value', 'reading_date'])
            ->whereNull('zone')
            ->orWhere('zone', '')
            ->orderBy('reading_date', 'desc')
            ->limit($readingsCount + 1)
            ->get();
        
        if ($readings->count() < 2) {
            return null;
        }
        
        $consumptions = [];
        $previousValue = null;
        
        foreach ($readings->sortBy('reading_date') as $reading) {
            if ($previousValue !== null) {
                $consumptions[] = $reading->value - $previousValue;
            }
            $previousValue = $reading->value;
        }
        
        if (empty($consumptions)) {
            return null;
        }
        
        return array_sum($consumptions) / count($consumptions);
    }

    /**
     * Store a new meter reading.
     * 
     * OPTIMIZED: Batch validation and cache invalidation
     */
    public function store(StoreMeterReadingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, &$reading) {
            $reading = MeterReading::create([
                'tenant_id' => auth()->user()->tenant_id,
                'meter_id' => $validated['meter_id'],
                'reading_date' => $validated['reading_date'],
                'value' => $validated['value'],
                'zone' => $validated['zone'] ?? null,
                'entered_by' => auth()->id(),
                'input_method' => $validated['input_method'] ?? 'manual',
                'validation_status' => $validated['validation_status'] ?? 'pending',
            ]);
            
            // Clear related caches
            Cache::forget("meter:last_reading:{$validated['meter_id']}");
            Cache::forget("dashboard_metrics_" . auth()->user()->tenant_id);
        });

        return response()->json([
            'id' => $reading->id,
            'meter_id' => $reading->meter_id,
            'reading_date' => $reading->reading_date->format('Y-m-d'),
            'value' => $reading->value,
            'zone' => $reading->zone,
            'entered_by' => $reading->entered_by,
            'created_at' => $reading->created_at->toIso8601String(),
        ], 201);
    }
}
