<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMeterReadingRequest;
use App\Http\Requests\UpdateMeterReadingRequest;
use App\Models\MeterReading;
use Illuminate\Http\JsonResponse;

/**
 * API controller for meter reading management.
 * 
 * Provides JSON endpoints for creating and updating meter readings.
 * All responses follow a consistent JSON structure with appropriate HTTP status codes.
 * 
 * Requirements:
 * - 1.1: Store reading with entered_by user ID and timestamp
 * - 1.2: Validate monotonicity (reading cannot be lower than previous)
 * - 1.3: Validate temporal validity (reading date not in future)
 * - 1.4: Maintain audit trail of changes
 * - 1.5: Handle multi-zone readings for electricity meters
 * 
 * @package App\Http\Controllers\Api
 */
final class MeterReadingApiController extends Controller
{
    /**
     * Store a newly created meter reading.
     * 
     * Creates a new meter reading with automatic tenant scoping and user tracking.
     * Handles both single-zone and multi-zone meters (electricity day/night).
     * 
     * Request body:
     * {
     *   "meter_id": 1,
     *   "reading_date": "2024-01-15",
     *   "value": 1234.56,
     *   "zone": "day" // Optional, required for multi-zone meters
     * }
     * 
     * Success response (201):
     * {
     *   "id": 123,
     *   "meter_id": 1,
     *   "reading_date": "2024-01-15",
     *   "value": "1234.56",
     *   "zone": "day",
     *   "entered_by": 5,
     *   "created_at": "2024-01-15T10:30:00Z"
     * }
     * 
     * Error response (422):
     * {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "value": ["Reading cannot be lower than previous reading (1200.00)"]
     *   }
     * }
     * 
     * @param StoreMeterReadingRequest $request Validated request
     * @return JsonResponse Created reading with 201 status
     */
    public function store(StoreMeterReadingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Create reading with automatic tenant scoping and user tracking
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

    /**
     * Update an existing meter reading with audit trail.
     * 
     * Updates a meter reading value with full validation and audit tracking.
     * The MeterReadingObserver automatically:
     * - Creates audit record with old/new values and change reason
     * - Recalculates affected draft invoices
     * - Prevents recalculation of finalized invoices
     * 
     * Request body:
     * {
     *   "value": 1250.00,
     *   "change_reason": "Corrected misread digit from 1234 to 1250",
     *   "reading_date": "2024-01-15", // Optional
     *   "zone": "day" // Optional
     * }
     * 
     * Success response (200):
     * {
     *   "id": 123,
     *   "meter_id": 1,
     *   "reading_date": "2024-01-15",
     *   "value": "1250.00",
     *   "zone": "day",
     *   "entered_by": 5,
     *   "updated_at": "2024-01-16T14:20:00Z",
     *   "audit": {
     *     "old_value": "1234.56",
     *     "new_value": "1250.00",
     *     "change_reason": "Corrected misread digit from 1234 to 1250",
     *     "changed_by": 5
     *   }
     * }
     * 
     * Error response (422):
     * {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "value": ["Reading cannot be lower than previous reading (1200.00)"],
     *     "change_reason": ["The change reason must be at least 10 characters."]
     *   }
     * }
     * 
     * @param UpdateMeterReadingRequest $request Validated request with new value and change reason
     * @param MeterReading $meterReading The reading to update
     * @return JsonResponse Updated reading with 200 status
     */
    public function update(
        UpdateMeterReadingRequest $request,
        MeterReading $meterReading
    ): JsonResponse {
        $validated = $request->validated();
        
        // Store old value for response
        $oldValue = $meterReading->value;
        
        // Set change_reason for the observer to use in audit trail
        $meterReading->change_reason = $validated['change_reason'];
        
        // Update the reading - observer will automatically create audit record
        $meterReading->update([
            'value' => $validated['value'],
            'reading_date' => $validated['reading_date'] ?? $meterReading->reading_date,
            'zone' => $validated['zone'] ?? $meterReading->zone,
        ]);

        // Reload to get fresh data
        $meterReading->refresh();

        return response()->json([
            'id' => $meterReading->id,
            'meter_id' => $meterReading->meter_id,
            'reading_date' => $meterReading->reading_date->format('Y-m-d'),
            'value' => $meterReading->value,
            'zone' => $meterReading->zone,
            'entered_by' => $meterReading->entered_by,
            'updated_at' => $meterReading->updated_at->toIso8601String(),
            'audit' => [
                'old_value' => $oldValue,
                'new_value' => $meterReading->value,
                'change_reason' => $validated['change_reason'],
                'changed_by' => auth()->id(),
            ],
        ], 200);
    }

    /**
     * Display the specified meter reading.
     * 
     * Returns a single meter reading with related data.
     * 
     * Success response (200):
     * {
     *   "id": 123,
     *   "meter_id": 1,
     *   "reading_date": "2024-01-15",
     *   "value": "1234.56",
     *   "zone": "day",
     *   "entered_by": 5,
     *   "created_at": "2024-01-15T10:30:00Z",
     *   "updated_at": "2024-01-15T10:30:00Z",
     *   "consumption": "34.56",
     *   "meter": {
     *     "id": 1,
     *     "serial_number": "LT-2024-001",
     *     "type": "electricity",
     *     "supports_zones": true
     *   }
     * }
     * 
     * @param MeterReading $meterReading The reading to display
     * @return JsonResponse Reading details with 200 status
     */
    public function show(MeterReading $meterReading): JsonResponse
    {
        $meterReading->load(['meter', 'enteredBy']);

        return response()->json([
            'id' => $meterReading->id,
            'meter_id' => $meterReading->meter_id,
            'reading_date' => $meterReading->reading_date->format('Y-m-d'),
            'value' => $meterReading->value,
            'zone' => $meterReading->zone,
            'entered_by' => $meterReading->entered_by,
            'created_at' => $meterReading->created_at->toIso8601String(),
            'updated_at' => $meterReading->updated_at->toIso8601String(),
            'consumption' => $meterReading->getConsumption(),
            'meter' => [
                'id' => $meterReading->meter->id,
                'serial_number' => $meterReading->meter->serial_number,
                'type' => $meterReading->meter->type->value,
                'supports_zones' => $meterReading->meter->supports_zones,
            ],
        ], 200);
    }
}
