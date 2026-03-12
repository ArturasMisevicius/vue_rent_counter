<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\BulkMeterReadingRequest;
use App\Http\Requests\StoreMeterReadingRequest;
use App\Http\Requests\UpdateMeterReadingRequest;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Services\MeterReadingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeterReadingController extends Controller
{
    public function __construct(
        private MeterReadingService $meterReadingService
    ) {}

    /**
     * Display a listing of meter readings.
     */
    public function index(): View
    {
        $readings = MeterReading::with(['meter.property', 'enteredBy'])
            ->latest('reading_date')
            ->paginate(50);

        return view('meter-readings.index', compact('readings'));
    }

    /**
     * Show the form for creating a new meter reading.
     */
    public function create(): View
    {
        return view('meter-readings.create');
    }

    /**
     * Store a newly created meter reading.
     */
    public function store(StoreMeterReadingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['entered_by'] = auth()->id();

        MeterReading::create($validated);

        return redirect()->route('meter-readings.index')
            ->with('success', __('notifications.meter_reading.created'));
    }

    /**
     * Display the specified meter reading.
     */
    public function show(MeterReading $meterReading): View
    {
        $meterReading->load(['meter', 'enteredBy', 'auditTrail']);

        return view('meter-readings.show', compact('meterReading'));
    }

    /**
     * Show the form for editing a meter reading.
     */
    public function edit(MeterReading $meterReading): View
    {
        $meters = Meter::with('property')->get();

        return view('meter-readings.edit', compact('meterReading', 'meters'));
    }

    /**
     * Update the specified meter reading.
     */
    public function update(UpdateMeterReadingRequest $request, MeterReading $meterReading): RedirectResponse
    {
        $validated = $request->validated();

        // Set change_reason for the observer to use in audit trail
        $meterReading->change_reason = $request->input('change_reason');

        // Update the reading - observer will automatically create audit record
        $meterReading->update($validated);

        return redirect()->route('meter-readings.index')
            ->with('success', __('notifications.meter_reading.updated'));
    }

    /**
     * Remove the specified meter reading.
     */
    public function destroy(MeterReading $meterReading): RedirectResponse
    {
        $meterReading->delete();

        return redirect()->route('meter-readings.index')
            ->with('success', __('notifications.meter_reading.deleted'));
    }

    /**
     * Display audit trail for a meter reading.
     */
    public function audit(MeterReading $meterReading): View
    {
        $audits = $meterReading->auditTrail()->with('changedByUser')->get();

        return view('meter-readings.audit', compact('meterReading', 'audits'));
    }

    /**
     * Store multiple meter readings at once.
     */
    public function bulk(BulkMeterReadingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        foreach ($validated['readings'] as $reading) {
            $reading['tenant_id'] = auth()->user()->tenant_id;
            $reading['entered_by'] = auth()->id();
            MeterReading::create($reading);
        }

        return back()->with('success', __('notifications.meter_reading.bulk_created'));
    }

    /**
     * Export meter readings to CSV/Excel.
     */
    public function export(Request $request): JsonResponse
    {
        // Future: Export to CSV/Excel
        return response()->json(['message' => __('meter_readings.errors.export_pending')]);
    }
}
