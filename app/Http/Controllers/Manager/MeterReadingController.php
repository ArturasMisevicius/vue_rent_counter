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
        if ($groupBy === 'meter_type') {
            $groupBy = 'service';
        }

        $propertyFilter = request('property_id');
        $serviceFilter = request('service') ?: null;
        if (!$serviceFilter && request()->filled('meter_type')) {
            $serviceFilter = 'type:' . (string) request('meter_type');
        }

        $query = MeterReading::with([
            'meter.property',
            'meter.serviceConfiguration.utilityService',
            'enteredBy',
        ])
            ->latest('reading_date');

        // Apply filters
        if ($propertyFilter) {
            $query->whereHas('meter', function ($q) use ($propertyFilter) {
                $q->where('property_id', $propertyFilter);
            });
        }

        if ($serviceFilter) {
            $query->whereHas('meter', function ($q) use ($serviceFilter) {
                [$kind, $value] = array_pad(explode(':', (string) $serviceFilter, 2), 2, null);

                if ($kind === 'utility' && is_numeric($value)) {
                    $q->whereHas('serviceConfiguration', fn ($sq) => $sq->where('utility_service_id', (int) $value));
                    return;
                }

                if ($kind === 'type' && is_string($value) && $value !== '') {
                    $q->whereNull('service_configuration_id')->where('type', $value);
                }
            });
        }

        $properties = Property::with('meters.serviceConfiguration.utilityService')
            ->orderBy('address')
            ->get();

        $metersForOptions = $properties->flatMap(fn (Property $property) => $property->meters);

        $utilityServices = $metersForOptions
            ->map(fn (Meter $meter) => $meter->serviceConfiguration?->utilityService)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $legacyMeterTypes = $metersForOptions
            ->filter(fn (Meter $meter) => $meter->serviceConfiguration === null)
            ->map(fn (Meter $meter) => $meter->type?->value)
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->unique()
            ->sort()
            ->values();

        $meterTypeLabels = MeterType::labels();
        $serviceFilterOptions = [];

        foreach ($utilityServices as $service) {
            $unit = $service->unit_of_measurement ? " ({$service->unit_of_measurement})" : '';
            $serviceFilterOptions["utility:{$service->id}"] = "{$service->name}{$unit}";
        }

        foreach ($legacyMeterTypes as $type) {
            $label = $meterTypeLabels[$type] ?? ucfirst(str_replace('_', ' ', (string) $type));
            $serviceFilterOptions["type:{$type}"] = "Legacy: {$label}";
        }
        
        // Get readings for grouping or pagination
        if ($groupBy === 'property') {
            $readings = $query->get()->groupBy('meter.property_id');
            return view('manager.meter-readings.index', compact(
                'readings',
                'groupBy',
                'properties',
                'meterTypeLabels',
                'serviceFilterOptions',
                'serviceFilter',
            ));
        } elseif ($groupBy === 'service') {
            $readings = $query->get()->groupBy(function (MeterReading $reading): string {
                $utilityServiceId = $reading->meter?->serviceConfiguration?->utility_service_id;

                if (is_int($utilityServiceId)) {
                    return "utility:{$utilityServiceId}";
                }

                return 'type:' . (string) $reading->meter?->type?->value;
            });

            return view('manager.meter-readings.index', compact(
                'readings',
                'groupBy',
                'properties',
                'meterTypeLabels',
                'serviceFilterOptions',
                'serviceFilter',
            ));
        } else {
            $readings = $query->paginate(50);
            return view('manager.meter-readings.index', compact(
                'readings',
                'groupBy',
                'properties',
                'meterTypeLabels',
                'serviceFilterOptions',
                'serviceFilter',
            ));
        }
    }

    /**
     * Show the form for creating a new meter reading.
     * 
     * Displays the meter reading form component with:
     * - All meters for the authenticated user's tenant
     * - All properties with their meters for filtering
     * - All providers for tariff selection
     * 
     * The form uses the x-meter-reading-form component which provides:
     * - Dynamic meter selection with property filtering
     * - AJAX-powered provider/tariff cascading dropdowns
     * - Previous reading display with consumption calculation
     * - Real-time validation (monotonicity, future dates)
     * - Charge preview based on selected tariff
     * - Multi-zone support for electricity meters (day/night)
     * 
     * Requirements:
     * - 10.1: Dynamic meter selection with property filtering
     * - 10.2: Real-time validation and charge preview
     * - 10.3: Multi-zone support for electricity meters
     * - 11.2: Authorization via MeterReadingPolicy
     * 
     * @return View Meter reading creation form
     * @throws \Illuminate\Auth\Access\AuthorizationException If user cannot create readings
     * 
     * @see \App\View\Components\MeterReadingForm
     * @see \App\Policies\MeterReadingPolicy::create()
     */
    public function create(): View
    {
        $this->authorize('create', MeterReading::class);

        $meters = Meter::with('property')->orderBy('serial_number')->get();
        $properties = Property::with('meters')->orderBy('address')->get();
        $providers = \App\Models\Provider::all();

        return view('manager.meter-readings.create', compact('meters', 'properties', 'providers'));
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
