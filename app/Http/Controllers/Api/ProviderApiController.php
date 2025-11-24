<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Provider;
use Illuminate\Http\JsonResponse;

class ProviderApiController extends Controller
{
    /**
     * Get active tariffs for a provider.
     */
    public function tariffs(Provider $provider): JsonResponse
    {
        $tariffs = $provider->tariffs()
            ->active()
            ->get()
            ->map(function ($tariff) {
                return [
                    'id' => $tariff->id,
                    'name' => $tariff->name,
                    'configuration' => $tariff->configuration,
                    'active_from' => $tariff->active_from->format('Y-m-d'),
                    'active_until' => $tariff->active_until?->format('Y-m-d'),
                ];
            });
        
        return response()->json($tariffs);
    }

    /**
     * List all properties for the authenticated user's tenant.
     */
    public function properties(): JsonResponse
    {
        $user = request()->user();

        $properties = Property::with(['building', 'meters'])
            ->where('tenant_id', $user->tenant_id)
            ->get()
            ->map(function ($property) {
                return [
                    'id' => $property->id,
                    'address' => $property->address,
                    'type' => $property->type->value,
                    'area_sqm' => $property->area_sqm,
                    'building_id' => $property->building_id,
                    'building_address' => $property->building?->address,
                    'meter_count' => $property->meters->count(),
                ];
            });

        return response()->json($properties);
    }

    /**
     * Get details for a specific property.
     */
    public function propertyDetails(Property $property): JsonResponse
    {
        $user = request()->user();

        if ($user && $property->tenant_id !== $user->tenant_id) {
            abort(404);
        }

        $property->load(['building', 'meters.readings' => function ($query) {
            $query->latest('reading_date')->limit(1);
        }]);

        return response()->json([
            'id' => $property->id,
            'address' => $property->address,
            'type' => $property->type->value,
            'area_sqm' => $property->area_sqm,
            'building_id' => $property->building_id,
            'building' => $property->building ? [
                'id' => $property->building->id,
                'address' => $property->building->address,
                'total_apartments' => $property->building->total_apartments,
            ] : null,
            'meters' => $property->meters->map(function ($meter) {
                $lastReading = $meter->readings->first();
                return [
                    'id' => $meter->id,
                    'serial_number' => $meter->serial_number,
                    'type' => $meter->type->value,
                    'supports_zones' => $meter->supports_zones,
                    'installation_date' => $meter->installation_date->format('Y-m-d'),
                    'last_reading' => $lastReading ? [
                        'value' => $lastReading->value,
                        'date' => $lastReading->reading_date->format('Y-m-d'),
                        'zone' => $lastReading->zone,
                    ] : null,
                ];
            }),
        ]);
    }
}
