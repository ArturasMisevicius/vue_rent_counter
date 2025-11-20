<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Property;
use Illuminate\Http\Request;

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer'],
            'address' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:apartment,house'],
            'area_sqm' => ['required', 'numeric', 'min:0'],
            'building_id' => ['nullable', 'exists:buildings,id'],
        ]);

        Property::create($validated);

        return redirect()->route('properties.index')
            ->with('success', 'Property created successfully.');
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

    public function update(Request $request, Property $property)
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer'],
            'address' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:apartment,house'],
            'area_sqm' => ['required', 'numeric', 'min:0'],
            'building_id' => ['nullable', 'exists:buildings,id'],
        ]);

        $property->update($validated);

        return redirect()->route('properties.index')
            ->with('success', 'Property updated successfully.');
    }

    public function destroy(Property $property)
    {
        $property->delete();

        return redirect()->route('properties.index')
            ->with('success', 'Property deleted successfully.');
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
