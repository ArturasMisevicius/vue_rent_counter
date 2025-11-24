<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMeterReadingRequest;
use App\Models\Meter;
use App\Models\MeterReading;
use Illuminate\Http\Request;

class MeterReadingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tenant = $user->tenant ?? null;
        $property = $user->property;
        
        $readings = $tenant 
            ? $tenant->meterReadings()->with('meter')->latest('reading_date')->paginate(20)
            : collect();

        // For submission form, load meters for the assigned property (if any)
        $properties = $property ? collect([$property->load('meters')]) : collect();

        return view('tenant.meter-readings.index', compact('readings', 'properties'));
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

    /**
     * Allow tenants to submit a reading for their own meter.
     */
    public function store(StoreMeterReadingRequest $request)
    {
        $user = $request->user();
        $property = $user->property;
        $tenant = $user->tenant;

        if (!$property || !$tenant) {
            abort(403, 'No property assigned.');
        }

        $validated = $request->validated();
        $meter = Meter::where('id', $validated['meter_id'])
            ->where('property_id', $property->id)
            ->firstOrFail();

        // Ensure tenant cannot submit for other tenants
        if ($meter->property_id !== $property->id) {
            abort(403);
        }

        // Monotonicity check: value must be >= latest reading
        $latest = $meter->readings()->latest('reading_date')->first();
        if ($latest && $validated['value'] < $latest->value) {
            return back()
                ->withInput()
                ->withErrors(['value' => 'Reading must be greater than or equal to the previous value (' . number_format($latest->value, 2) . ').']);
        }

        $reading = $meter->readings()->create([
            'tenant_id' => $tenant->id,
            'meter_id' => $meter->id,
            'reading_date' => $validated['reading_date'],
            'value' => $validated['value'],
            'zone' => $validated['zone'] ?? null,
            'entered_by_user_id' => $user->id,
        ]);

        return redirect()
            ->route('tenant.meter-readings.show', $reading)
            ->with('success', 'Reading submitted successfully.');
    }
}
