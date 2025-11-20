<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\MeterReading;
use Illuminate\Http\Request;

class MeterReadingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tenant = $user->tenant ?? null;
        
        $readings = $tenant 
            ? $tenant->meterReadings()->with('meter')->latest('reading_date')->paginate(20)
            : collect();

        return view('tenant.meter-readings.index', compact('readings'));
    }

    public function show(Request $request, MeterReading $meterReading)
    {
        $user = $request->user();
        $tenant = $user->tenant ?? null;
        $property = $tenant?->property;
        
        if (!$property || $meterReading->meter->property_id !== $property->id) {
            abort(403);
        }

        return view('tenant.meter-readings.show', compact('meterReading'));
    }
}
