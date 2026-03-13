<?php

namespace App\Http\Controllers;

use App\DTOs\PropertyCreateDTO;
use App\DTOs\PropertyUpdateDTO;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Models\Property;
use App\Services\PropertyManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PropertyController extends Controller
{
    public function __construct(
        private readonly PropertyManagementService $propertyService
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'type', 'city', 'is_active', 'owner_id']);
        $properties = $this->propertyService->getProperties($filters, $request->get('per_page', 15));

        return view('properties.index', compact('properties', 'filters'));
    }

    public function show(Property $property): View
    {
        $analytics = $this->propertyService->getPropertyAnalytics($property->id);

        return view('properties.show', compact('property', 'analytics'));
    }

    public function create(): View
    {
        return view('properties.create');
    }

    public function store(StorePropertyRequest $request): RedirectResponse
    {
        try {
            $dto = PropertyCreateDTO::fromArray($request->validated());
            $property = $this->propertyService->createProperty($dto);

            return redirect()
                ->route('properties.show', $property)
                ->with('success', 'Property created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function edit(Property $property): View
    {
        return view('properties.edit', compact('property'));
    }

    public function update(UpdatePropertyRequest $request, Property $property): RedirectResponse
    {
        try {
            $dto = PropertyUpdateDTO::fromArray($request->validated());
            $this->propertyService->updateProperty($property->id, $dto);

            return redirect()
                ->route('properties.show', $property)
                ->with('success', 'Property updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(Property $property): RedirectResponse
    {
        try {
            $this->propertyService->deactivateProperty($property->id, 'Deleted by user');

            return redirect()
                ->route('properties.index')
                ->with('success', 'Property deactivated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}