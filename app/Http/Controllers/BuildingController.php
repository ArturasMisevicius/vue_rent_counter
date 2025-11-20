<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Services\GyvatukasCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    public function index()
    {
        $buildings = Building::withCount('properties')->paginate(20);
        return view('buildings.index', compact('buildings'));
    }

    public function create()
    {
        return view('buildings.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer'],
            'address' => ['required', 'string', 'max:255'],
            'total_apartments' => ['required', 'integer', 'min:1'],
        ]);

        Building::create($validated);

        return redirect()->route('buildings.index')
            ->with('success', 'Building created successfully.');
    }

    public function show(Building $building)
    {
        $building->load('properties');
        return view('buildings.show', compact('building'));
    }

    public function edit(Building $building)
    {
        return view('buildings.edit', compact('building'));
    }

    public function update(Request $request, Building $building)
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer'],
            'address' => ['required', 'string', 'max:255'],
            'total_apartments' => ['required', 'integer', 'min:1'],
        ]);

        $building->update($validated);

        return redirect()->route('buildings.index')
            ->with('success', 'Building updated successfully.');
    }

    public function destroy(Building $building)
    {
        $building->delete();

        return redirect()->route('buildings.index')
            ->with('success', 'Building deleted successfully.');
    }

    public function calculateGyvatukas(Request $request, Building $building)
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        $average = $building->calculateSummerAverage($startDate, $endDate);

        return back()->with('success', "Gyvatukas calculated: {$average} kWh");
    }

    public function properties(Building $building)
    {
        $properties = $building->properties()->paginate(20);
        return view('buildings.properties', compact('building', 'properties'));
    }
}
