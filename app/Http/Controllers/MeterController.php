<?php

namespace App\Http\Controllers;

use App\Models\Meter;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MeterController extends Controller
{
    public function index()
    {
        $meters = Meter::with('property')->paginate(20);
        return view('meters.index', compact('meters'));
    }

    public function create()
    {
        $properties = Property::all();
        return view('meters.create', compact('properties'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer'],
            'serial_number' => ['required', 'string', 'max:255', 'unique:meters'],
            'type' => ['required', 'in:electricity,water_cold,water_hot,heating'],
            'property_id' => ['required', 'exists:properties,id'],
            'installation_date' => ['required', 'date'],
            'supports_zones' => ['boolean'],
        ]);

        Meter::create($validated);

        return redirect()->route('meters.index')
            ->with('success', 'Meter created successfully.');
    }

    public function show(Meter $meter)
    {
        $meter->load(['property', 'readings']);
        return view('meters.show', compact('meter'));
    }

    public function edit(Meter $meter)
    {
        $properties = Property::all();
        return view('meters.edit', compact('meter', 'properties'));
    }

    public function update(Request $request, Meter $meter)
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer'],
            'serial_number' => ['required', 'string', 'max:255', 'unique:meters,serial_number,' . $meter->id],
            'type' => ['required', 'in:electricity,water_cold,water_hot,heating'],
            'property_id' => ['required', 'exists:properties,id'],
            'installation_date' => ['required', 'date'],
            'supports_zones' => ['boolean'],
        ]);

        $meter->update($validated);

        return redirect()->route('meters.index')
            ->with('success', 'Meter updated successfully.');
    }

    public function destroy(Meter $meter)
    {
        $meter->delete();

        return redirect()->route('meters.index')
            ->with('success', 'Meter deleted successfully.');
    }

    public function readings(Meter $meter)
    {
        $readings = $meter->readings()->latest('reading_date')->paginate(50);
        return view('meters.readings', compact('meter', 'readings'));
    }

    public function pendingReadings()
    {
        $meters = Meter::whereDoesntHave('readings', function ($query) {
            $query->where('reading_date', '>=', Carbon::now()->startOfMonth());
        })->with('property')->paginate(50);

        return view('meters.pending-readings', compact('meters'));
    }
}
