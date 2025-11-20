<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\Tariff;
use Illuminate\Http\Request;

class TariffController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Tariff::class);
        
        $query = Tariff::with('provider');
        
        // Handle sorting
        $sortColumn = $request->input('sort', 'active_from');
        $sortDirection = $request->input('direction', 'desc');
        
        // Validate sort column
        $allowedColumns = ['name', 'active_from', 'active_until', 'created_at'];
        if (in_array($sortColumn, $allowedColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->orderBy('active_from', 'desc');
        }
        
        $tariffs = $query->paginate(20)->withQueryString();
        return view('admin.tariffs.index', compact('tariffs'));
    }

    public function create()
    {
        $this->authorize('create', Tariff::class);
        
        $providers = Provider::orderBy('name')->get();
        return view('admin.tariffs.create', compact('providers'));
    }

    public function store(\App\Http\Requests\StoreTariffRequest $request)
    {
        $this->authorize('create', Tariff::class);
        
        $validated = $request->validated();

        Tariff::create($validated);

        return redirect()->route('admin.tariffs.index')
            ->with('success', 'Tariff created successfully.');
    }

    public function show(Tariff $tariff)
    {
        $this->authorize('view', $tariff);
        
        $tariff->load('provider');
        
        // Get version history (tariffs with same name and provider)
        $versionHistory = Tariff::where('provider_id', $tariff->provider_id)
            ->where('name', $tariff->name)
            ->where('id', '!=', $tariff->id)
            ->orderBy('active_from', 'desc')
            ->get();
        
        return view('admin.tariffs.show', compact('tariff', 'versionHistory'));
    }

    public function edit(Tariff $tariff)
    {
        $this->authorize('update', $tariff);
        
        $providers = Provider::orderBy('name')->get();
        return view('admin.tariffs.edit', compact('tariff', 'providers'));
    }

    public function update(\App\Http\Requests\StoreTariffRequest $request, Tariff $tariff)
    {
        $this->authorize('update', $tariff);
        
        $validated = $request->validated();
        
        // If creating a new version, preserve the old tariff and create a new one
        if ($request->boolean('create_new_version')) {
            // Set end date for current tariff to day before new version starts
            $newActiveFrom = \Carbon\Carbon::parse($validated['active_from']);
            $tariff->update(['active_until' => $newActiveFrom->copy()->subDay()]);
            
            // Create new version
            $newTariff = Tariff::create([
                'provider_id' => $validated['provider_id'],
                'name' => $validated['name'],
                'configuration' => $validated['configuration'],
                'active_from' => $validated['active_from'],
                'active_until' => $validated['active_until'] ?? null,
            ]);
            
            return redirect()->route('admin.tariffs.show', $newTariff)
                ->with('success', 'New tariff version created successfully.');
        }

        // Otherwise, update the existing tariff
        $tariff->update($validated);

        return redirect()->route('admin.tariffs.show', $tariff)
            ->with('success', 'Tariff updated successfully.');
    }

    public function destroy(Tariff $tariff)
    {
        $this->authorize('delete', $tariff);
        
        $tariff->delete();

        return redirect()->route('admin.tariffs.index')
            ->with('success', 'Tariff deleted successfully.');
    }
}
