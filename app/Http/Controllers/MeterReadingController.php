<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMeterReadingRequest;
use App\Http\Requests\UpdateMeterReadingRequest;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Services\MeterReadingService;
use Illuminate\Http\Request;

class MeterReadingController extends Controller
{
    public function __construct(
        private MeterReadingService $meterReadingService
    ) {}

    /**
     * Display a listing of meter readings.
     */
    public function index(): \Illuminate\View\View
    {
        $readings = MeterReading::with(['meter.property', 'enteredBy'])
            ->latest('reading_date')
            ->paginate(50);
        
        return view('meter-readings.index', compact('readings'));
    }

    /**
     * Show the form for creating a new meter reading.
     */
    public function create(): \Illuminate\View\View
    {
        $meters = Meter::with('property')->get();
        $providers = \App\Models\Provider::all();
        return view('meter-readings.create', compact('meters', 'providers'));
    }

    /**
     * Store a newly created meter reading.
     */
    public function store(StoreMeterReadingRequest $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validated();
        $validated['entered_by'] = auth()->id();

        MeterReading::create($validated);

        return redirect()->route('meter-readings.index')
            ->with('success', 'Meter reading created successfully.');
    }

    /**
     * Display the specified meter reading.
     */
    public function show(MeterReading $meterReading): \Illuminate\View\View
    {
        $meterReading->load(['meter', 'enteredBy', 'auditTrail']);
        return view('meter-readings.show', compact('meterReading'));
    }

    /**
     * Show the form for editing a meter reading.
     */
    public function edit(MeterReading $meterReading): \Illuminate\View\View
    {
        $meters = Meter::with('property')->get();
        return view('meter-readings.edit', compact('meterReading', 'meters'));
    }

    /**
     * Update the specified meter reading.
     */
    public function update(UpdateMeterReadingRequest $request, MeterReading $meterReading): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validated();
        
        // Set change_reason for the observer to use in audit trail
        $meterReading->change_reason = $request->input('change_reason');
        
        // Update the reading - observer will automatically create audit record
        $meterReading->update($validated);

        return redirect()->route('meter-readings.index')
            ->with('success', 'Meter reading updated successfully.');
    }

    /**
     * Remove the specified meter reading.
     */
    public function destroy(MeterReading $meterReading): \Illuminate\Http\RedirectResponse
    {
        $meterReading->delete();

        return redirect()->route('meter-readings.index')
            ->with('success', 'Meter reading deleted successfully.');
    }

    /**
     * Display audit trail for a meter reading.
     */
    public function audit(MeterReading $meterReading): \Illuminate\View\View
    {
        $audits = $meterReading->auditTrail()->with('changedByUser')->get();
        return view('meter-readings.audit', compact('meterReading', 'audits'));
    }

    /**
     * Store multiple meter readings at once.
     */
    public function bulk(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'readings' => ['required', 'array'],
            'readings.*.meter_id' => ['required', 'exists:meters,id'],
            'readings.*.reading_date' => ['required', 'date'],
            'readings.*.value' => ['required', 'numeric', 'min:0'],
            'readings.*.zone' => ['nullable', 'string'],
        ]);

        foreach ($validated['readings'] as $reading) {
            $reading['tenant_id'] = auth()->user()->tenant_id;
            $reading['entered_by'] = auth()->id();
            MeterReading::create($reading);
        }

        return back()->with('success', 'Bulk readings created successfully.');
    }

    /**
     * Export meter readings to CSV/Excel.
     */
    public function export(Request $request): \Illuminate\Http\JsonResponse
    {
        // Future: Export to CSV/Excel
        return response()->json(['message' => 'Export not yet implemented']);
    }
}
