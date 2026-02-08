<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBuildingRequest;
use App\Http\Requests\UpdateBuildingRequest;
use App\Models\Building;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BuildingController extends Controller
{
    /**
     * Display a listing of buildings.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Building::class);

        $query = Building::withCount('properties');
        
        // Handle sorting
        $sortColumn = $request->input('sort', 'address');
        $sortDirection = $request->input('direction', 'asc');
        
        // Validate sort column
        $allowedColumns = ['address', 'total_apartments', 'created_at'];
        if (in_array($sortColumn, $allowedColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->orderBy('address');
        }

        $buildings = $query->paginate(20)->withQueryString();

        return view('pages.buildings.index-manager', compact('buildings'));
    }

    /**
     * Show the form for creating a new building.
     */
    public function create(): View
    {
        $this->authorize('create', Building::class);

        return view('pages.buildings.create-manager');
    }

    /**
     * Store a newly created building.
     */
    public function store(StoreBuildingRequest $request): RedirectResponse
    {
        $this->authorize('create', Building::class);

        $building = Building::create($request->validated());

        return redirect()
            ->route('manager.buildings.show', $building)
            ->with('success', __('notifications.building.created'));
    }

    /**
     * Display the specified building.
     */
    public function show(Building $building): View
    {
        $this->authorize('view', $building);

        $building->load(['properties.meters', 'properties.tenants']);

        return view('pages.buildings.show-manager', compact('building'));
    }

    /**
     * Show the form for editing the specified building.
     */
    public function edit(Building $building): View
    {
        $this->authorize('update', $building);

        return view('pages.buildings.edit-manager', compact('building'));
    }

    /**
     * Update the specified building.
     */
    public function update(UpdateBuildingRequest $request, Building $building): RedirectResponse
    {
        $this->authorize('update', $building);

        $building->update($request->validated());

        return redirect()
            ->route('manager.buildings.show', $building)
            ->with('success', __('notifications.building.updated'));
    }

    /**
     * Remove the specified building.
     */
    public function destroy(Building $building): RedirectResponse
    {
        $this->authorize('delete', $building);

        // Check if building has associated properties
        if ($building->properties()->exists()) {
            return back()->with('error', __('buildings.errors.has_properties'));
        }

        $building->delete();

        return redirect()
            ->route('manager.buildings.index')
            ->with('success', __('notifications.building.deleted'));
    }
}
