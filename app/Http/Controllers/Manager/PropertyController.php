<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Models\Building;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PropertyController extends Controller
{
    /**
     * Display a listing of properties.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Property::class);

        $query = Property::with(['building', 'tenants', 'meters'])
            ->withCount('meters');
        
        // Handle search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('address', 'like', "%{$search}%");
        }
        
        // Handle property type filter
        if ($request->filled('property_type')) {
            $query->where('property_type', $request->input('property_type'));
        }
        
        // Handle building filter
        if ($request->filled('building_id')) {
            $query->where('building_id', $request->input('building_id'));
        }
        
        // Handle sorting
        $sortColumn = $request->input('sort', 'address');
        $sortDirection = $request->input('direction', 'asc');
        
        // Validate sort column
        $allowedColumns = ['address', 'property_type', 'area_sqm', 'created_at'];
        if (in_array($sortColumn, $allowedColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->orderBy('address');
        }

        $properties = $query->paginate(20)->withQueryString();
        
        // Get buildings for filter dropdown
        $buildings = Building::orderBy('address')->get();

        return view('manager.properties.index', compact('properties', 'buildings'));
    }

    /**
     * Show the form for creating a new property.
     */
    public function create(): View
    {
        $this->authorize('create', Property::class);

        $buildings = Building::orderBy('address')->get();

        return view('manager.properties.create', compact('buildings'));
    }

    /**
     * Store a newly created property.
     */
    public function store(StorePropertyRequest $request): RedirectResponse
    {
        $this->authorize('create', Property::class);

        $property = Property::create($request->validated());

        return redirect()
            ->route('manager.properties.show', $property)
            ->with('success', __('notifications.property.created'));
    }

    /**
     * Display the specified property.
     */
    public function show(Property $property): View
    {
        $this->authorize('view', $property);

        $property->load(['building', 'tenants', 'meters.readings' => function ($query) {
            $query->latest('reading_date')->limit(1);
        }]);

        return view('manager.properties.show', compact('property'));
    }

    /**
     * Show the form for editing the specified property.
     */
    public function edit(Property $property): View
    {
        $this->authorize('update', $property);

        $buildings = Building::orderBy('address')->get();

        return view('manager.properties.edit', compact('property', 'buildings'));
    }

    /**
     * Update the specified property.
     */
    public function update(UpdatePropertyRequest $request, Property $property): RedirectResponse
    {
        $this->authorize('update', $property);

        $property->update($request->validated());

        return redirect()
            ->route('manager.properties.show', $property)
            ->with('success', __('notifications.property.updated'));
    }

    /**
     * Remove the specified property.
     */
    public function destroy(Property $property): RedirectResponse
    {
        $this->authorize('delete', $property);

        // Check if property has associated data
        if ($property->meters()->exists() || $property->tenants()->exists()) {
            return back()->with('error', __('properties.errors.has_relations'));
        }

        $property->delete();

        return redirect()
            ->route('manager.properties.index')
            ->with('success', __('notifications.property.deleted'));
    }
}
