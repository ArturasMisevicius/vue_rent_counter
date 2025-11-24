<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalculateGyvatukasRequest;
use App\Http\Requests\StoreBuildingRequest;
use App\Http\Requests\UpdateBuildingRequest;
use App\Models\Building;
use Carbon\Carbon;

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

    public function store(StoreBuildingRequest $request)
    {
        $validated = $request->validated();

        Building::create($validated);

        return redirect()->route('buildings.index')
            ->with('success', __('notifications.building.created'));
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

    public function update(UpdateBuildingRequest $request, Building $building)
    {
        $validated = $request->validated();

        $building->update($validated);

        return redirect()->route('buildings.index')
            ->with('success', __('notifications.building.updated'));
    }

    public function destroy(Building $building)
    {
        $building->delete();

        return redirect()->route('buildings.index')
            ->with('success', __('notifications.building.deleted'));
    }

    public function calculateGyvatukas(CalculateGyvatukasRequest $request, Building $building)
    {
        $startDate = Carbon::parse($request->validated('start_date'));
        $endDate = Carbon::parse($request->validated('end_date'));

        $average = $building->calculateSummerAverage($startDate, $endDate);

        return back()->with('success', __('notifications.building.gyvatukas', [
            'average' => $average,
        ]));
    }

    public function properties(Building $building)
    {
        $properties = $building->properties()->paginate(20);
        return view('buildings.properties', compact('building', 'properties'));
    }
}
