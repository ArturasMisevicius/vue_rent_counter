<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMeterReadingRequest;
use App\Models\Meter;
use App\Models\MeterReading;
use Illuminate\Http\JsonResponse;

class MeterApiController extends Controller
{
    /**
     * Get the last reading for a meter.
     */
    public function lastReading(Meter $meter): JsonResponse
    {
        // For meters with zones, get the latest readings for each zone
        if ($meter->supports_zones) {
            $dayReading = $meter->readings()
                ->where('zone', 'day')
                ->latest('reading_date')
                ->first();
            
            $nightReading = $meter->readings()
                ->where('zone', 'night')
                ->latest('reading_date')
                ->first();
            
            if (!$dayReading && !$nightReading) {
                return response()->json(null, 404);
            }
            
            return response()->json([
                'date' => $dayReading?->reading_date->format('Y-m-d') ?? $nightReading?->reading_date->format('Y-m-d'),
                'day_value' => $dayReading?->value,
                'night_value' => $nightReading?->value,
                'value' => ($dayReading?->value ?? 0) + ($nightReading?->value ?? 0),
            ]);
        }
        
        // For single-zone meters
        $lastReading = $meter->readings()
            ->latest('reading_date')
            ->first();
        
        if (!$lastReading) {
            return response()->json(null, 404);
        }
        
        return response()->json([
            'id' => $lastReading->id,
            'value' => $lastReading->value,
            'date' => $lastReading->reading_date->format('Y-m-d'),
            'zone' => $lastReading->zone,
        ]);
    }

    /**
     * Store a new meter reading.
     */
    public function store(StoreMeterReadingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $reading = MeterReading::create([
            'tenant_id' => auth()->user()->tenant_id,
            'meter_id' => $validated['meter_id'],
            'reading_date' => $validated['reading_date'],
            'value' => $validated['value'],
            'zone' => $validated['zone'] ?? null,
            'entered_by' => auth()->id(),
        ]);

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
