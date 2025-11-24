<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Models\Building;
use App\Models\Property;

class PropertyController extends Controller
{
    public function index()
    {
        $properties = Property::with('building')->paginate(20);
        return view('properties.index', compact('properties'));
    }

    public function create()
    {
        $buildings = Building::all();
        return view('properties.create', compact('buildings'));
    }

    public function store(StorePropertyRequest $request)
    {
        $validated = $request->validated();

        Property::create($validated);

        return redirect()->route('properties.index')
            ->with('success', __('notifications.property.created'));
    }

    public function show(Property $property)
    {
        $property->load(['building', 'tenants', 'meters']);
        return view('properties.show', compact('property'));
    }

    public function edit(Property $property)
    {
        $buildings = Building::all();
        return view('properties.edit', compact('property', 'buildings'));
    }

    public function update(UpdatePropertyRequest $request, Property $property)
    {
        $validated = $request->validated();

        $property->update($validated);

        return redirect()->route('properties.index')
            ->with('success', __('notifications.property.updated'));
    }

    public function destroy(Property $property)
    {
        $property->delete();

        return redirect()->route('properties.index')
            ->with('success', __('notifications.property.deleted'));
    }

    public function meters(Property $property)
    {
        $meters = $property->meters()->with('readings')->paginate(20);
        return view('properties.meters', compact('property', 'meters'));
    }

    public function tenants(Property $property)
    {
        $tenants = $property->tenants()->paginate(20);
        return view('properties.tenants', compact('property', 'tenants'));
    }

    public function invoices(Property $property)
    {
        $invoices = $property->tenants()
            ->with('invoices')
            ->get()
            ->pluck('invoices')
            ->flatten();
        
        return view('properties.invoices', compact('property', 'invoices'));
    }
}
