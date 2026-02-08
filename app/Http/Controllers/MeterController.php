<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMeterRequest;
use App\Http\Requests\UpdateMeterRequest;
use App\Models\Meter;
use App\Models\Property;
use Carbon\Carbon;

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

    public function store(StoreMeterRequest $request)
    {
        $validated = $request->validated();

        Meter::create($validated);

        return redirect()->route('meters.index')
            ->with('success', __('notifications.meter.created'));
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

    public function update(UpdateMeterRequest $request, Meter $meter)
    {
        $validated = $request->validated();

        $meter->update($validated);

        return redirect()->route('meters.index')
            ->with('success', __('notifications.meter.updated'));
    }

    public function destroy(Meter $meter)
    {
        $meter->delete();

        return redirect()->route('meters.index')
            ->with('success', __('notifications.meter.deleted'));
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
