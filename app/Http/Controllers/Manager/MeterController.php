<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMeterRequest;
use App\Http\Requests\UpdateMeterRequest;
use App\Models\Meter;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeterController extends Controller
{
    /**
     * Display a listing of meters.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Meter::class);

        $query = Meter::with(['property', 'serviceConfiguration.utilityService', 'readings' => function ($query) {
            $query->latest('reading_date')->limit(1);
        }]);
        
        // Handle search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('serial_number', 'like', "%{$search}%");
        }
        
        // Handle meter type filter
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
        
        // Handle property filter
        if ($request->filled('property_id')) {
            $query->where('property_id', $request->input('property_id'));
        }
        
        // Handle sorting
        $sortColumn = $request->input('sort', 'serial_number');
        $sortDirection = $request->input('direction', 'asc');
        
        // Validate sort column
        $allowedColumns = ['serial_number', 'type', 'installation_date', 'created_at'];
        if (in_array($sortColumn, $allowedColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->orderBy('serial_number');
        }

        $meters = $query->paginate(20)->withQueryString();
        
        // Get properties for filter dropdown
        $properties = Property::orderBy('address')->get();

        return view('pages.meters.index-manager', compact('meters', 'properties'));
    }

    /**
     * Show the form for creating a new meter.
     */
    public function create(): View
    {
        $this->authorize('create', Meter::class);

        $properties = Property::orderBy('address')->get();
        $serviceConfigurationOptions = $this->getServiceConfigurationOptions();

        return view('pages.meters.create-manager', compact('properties', 'serviceConfigurationOptions'));
    }

    /**
     * Store a newly created meter.
     */
    public function store(StoreMeterRequest $request): RedirectResponse
    {
        $this->authorize('create', Meter::class);

        $meter = Meter::create($request->validated());

        return redirect()
            ->route('manager.meters.show', $meter)
            ->with('success', __('notifications.meter.created'));
    }

    /**
     * Display the specified meter.
     */
    public function show(Meter $meter): View
    {
        $this->authorize('view', $meter);

        $meter->load(['property', 'serviceConfiguration.utilityService', 'readings' => function ($query) {
            $query->latest('reading_date')->limit(12);
        }]);

        // Prepare data for reading history graph
        $readingHistory = $meter->readings->map(function ($reading) {
            return [
                'date' => $reading->reading_date->format('M d'),
                'value' => $reading->getEffectiveValue(),
            ];
        })->reverse()->values();

        return view('pages.meters.show-manager', compact('meter', 'readingHistory'));
    }

    /**
     * Show the form for editing the specified meter.
     */
    public function edit(Meter $meter): View
    {
        $this->authorize('update', $meter);

        $properties = Property::orderBy('address')->get();
        $serviceConfigurationOptions = $this->getServiceConfigurationOptions();

        return view('pages.meters.edit-manager', compact('meter', 'properties', 'serviceConfigurationOptions'));
    }

    /**
     * Update the specified meter.
     */
    public function update(UpdateMeterRequest $request, Meter $meter): RedirectResponse
    {
        $this->authorize('update', $meter);

        $meter->update($request->validated());

        return redirect()
            ->route('manager.meters.show', $meter)
            ->with('success', __('notifications.meter.updated'));
    }

    /**
     * Remove the specified meter.
     */
    public function destroy(Meter $meter): RedirectResponse
    {
        $this->authorize('delete', $meter);

        // Check if meter has associated readings
        if ($meter->readings()->exists()) {
            return back()->with('error', __('meters.errors.has_readings'));
        }

        $meter->delete();

        return redirect()
            ->route('manager.meters.index')
            ->with('success', __('notifications.meter.deleted'));
    }

    /**
     * @return array<int, string>
     */
    private function getServiceConfigurationOptions(): array
    {
        return ServiceConfiguration::query()
            ->active()
            ->with([
                'property:id,address',
                'utilityService:id,name,unit_of_measurement',
            ])
            ->orderBy('property_id')
            ->orderBy('utility_service_id')
            ->get()
            ->mapWithKeys(function (ServiceConfiguration $configuration) {
                $propertyAddress = $configuration->property?->address ?? __('app.common.na');
                $serviceName = $configuration->utilityService?->name ?? __('app.common.na');
                $unit = $configuration->utilityService?->unit_of_measurement;

                $label = $unit ? "{$propertyAddress} — {$serviceName} ({$unit})" : "{$propertyAddress} — {$serviceName}";

                return [$configuration->id => $label];
            })
            ->all();
    }
}
