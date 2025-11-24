<?php

namespace App\Http\Controllers\Manager;

use App\Enums\MeterType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMeterReadingRequest;
use App\Http\Requests\UpdateMeterReadingRequest;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MeterReadingController extends Controller
{
    /**
     * Display a listing of meter readings grouped by property.
     */
    public function index(): View
    {
        $this->authorize('viewAny', MeterReading::class);

        $groupBy = request('group_by', 'none');
        $propertyFilter = request('property_id');
        $meterTypeFilter = request('meter_type');

        $query = MeterReading::with(['meter.property', 'enteredBy'])
            ->latest('reading_date');

        // Apply filters
        if ($propertyFilter) {
            $query->whereHas('meter', function ($q) use ($propertyFilter) {
                $q->where('property_id', $propertyFilter);
            });
        }

        if ($meterTypeFilter) {
            $query->whereHas('meter', function ($q) use ($meterTypeFilter) {
                $q->where('type', $meterTypeFilter);
            });
        }

        // Get meter type labels for filter dropdown
        $meterTypeLabels = MeterType::labels();
        
        // Get readings for grouping or pagination
        if ($groupBy === 'property') {
            $readings = $query->get()->groupBy('meter.property_id');
            $properties = Property::with('meters')->orderBy('address')->get();
            return view('manager.meter-readings.index', compact('readings', 'groupBy', 'properties', 'meterTypeLabels'));
        } elseif ($groupBy === 'meter_type') {
            $readings = $query->get()->groupBy('meter.type');
            $properties = Property::orderBy('address')->get();
            return view('manager.meter-readings.index', compact('readings', 'groupBy', 'properties', 'meterTypeLabels'));
        } else {
            $readings = $query->paginate(50);
            $properties = Property::orderBy('address')->get();
            return view('manager.meter-readings.index', compact('readings', 'groupBy', 'properties', 'meterTypeLabels'));
        }
    }

    /**
     * Show the form for creating a new meter reading.
     */
    public function create(): View
    {
        $this->authorize('create', MeterReading::class);

        $meters = Meter::with('property')->orderBy('serial_number')->get();
        $properties = Property::with('meters')->orderBy('address')->get();

        return view('manager.meter-readings.create', compact('meters', 'properties'));
    }

    /**
     * Store a newly created meter reading.
     */
    public function store(StoreMeterReadingRequest $request): RedirectResponse
    {
        $this->authorize('create', MeterReading::class);

        $validated = $request->validated();
        $validated['entered_by'] = auth()->id();

        MeterReading::create($validated);

        return redirect()
            ->route('manager.meter-readings.index')
            ->with('success', __('notifications.meter_reading.created'));
    }

    /**
     * Display the specified meter reading.
     */
    public function show(MeterReading $meterReading): View
    {
        $this->authorize('view', $meterReading);

        $meterReading->load(['meter.property', 'enteredBy', 'auditTrail']);

        return view('manager.meter-readings.show', compact('meterReading'));
    }

    /**
     * Show the form for editing a meter reading (corrections).
     */
    public function edit(MeterReading $meterReading): View
    {
        $this->authorize('update', $meterReading);

        $meters = Meter::with('property')->orderBy('serial_number')->get();

        return view('manager.meter-readings.edit', compact('meterReading', 'meters'));
    }

    /**
     * Update the specified meter reading (correction with audit trail).
     */
    public function update(UpdateMeterReadingRequest $request, MeterReading $meterReading): RedirectResponse
    {
        $this->authorize('update', $meterReading);

        $validated = $request->validated();
        
        // Set change_reason for the observer to use in audit trail
        $meterReading->change_reason = $request->input('change_reason');
        
        // Update the reading - observer will automatically create audit record
        $meterReading->update($validated);

        return redirect()
            ->route('manager.meter-readings.show', $meterReading)
            ->with('success', __('notifications.meter_reading.corrected'));
    }

    /**
     * Remove the specified meter reading.
     */
    public function destroy(MeterReading $meterReading): RedirectResponse
    {
        $this->authorize('delete', $meterReading);

        $meterReading->delete();

        return redirect()
            ->route('manager.meter-readings.index')
            ->with('success', __('notifications.meter_reading.deleted'));
    }
}
