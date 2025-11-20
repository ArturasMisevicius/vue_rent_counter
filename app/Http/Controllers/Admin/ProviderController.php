<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Provider::class);
        
        $query = Provider::withCount('tariffs');
        
        // Handle sorting
        $sortColumn = $request->input('sort', 'name');
        $sortDirection = $request->input('direction', 'asc');
        
        // Validate sort column
        $allowedColumns = ['name', 'service_type', 'created_at'];
        if (in_array($sortColumn, $allowedColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->orderBy('name');
        }
        
        $providers = $query->paginate(20)->withQueryString();
        return view('admin.providers.index', compact('providers'));
    }

    public function create()
    {
        $this->authorize('create', Provider::class);
        
        return view('admin.providers.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Provider::class);
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'service_type' => ['required', 'in:electricity,water,heating'],
            'contact_info' => ['nullable', 'string'],
        ]);

        // Convert contact_info to array if it's a string
        if (isset($validated['contact_info']) && !empty($validated['contact_info'])) {
            $validated['contact_info'] = ['notes' => $validated['contact_info']];
        }

        Provider::create($validated);

        return redirect()->route('admin.providers.index')
            ->with('success', 'Provider created successfully.');
    }

    public function show(Provider $provider)
    {
        $this->authorize('view', $provider);
        
        $provider->load(['tariffs' => function ($query) {
            $query->orderBy('active_from', 'desc');
        }]);
        return view('admin.providers.show', compact('provider'));
    }

    public function edit(Provider $provider)
    {
        $this->authorize('update', $provider);
        
        return view('admin.providers.edit', compact('provider'));
    }

    public function update(Request $request, Provider $provider)
    {
        $this->authorize('update', $provider);
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'service_type' => ['required', 'in:electricity,water,heating'],
            'contact_info' => ['nullable', 'string'],
        ]);

        // Convert contact_info to array if it's a string
        if (isset($validated['contact_info']) && !empty($validated['contact_info'])) {
            $validated['contact_info'] = ['notes' => $validated['contact_info']];
        } elseif (isset($validated['contact_info']) && empty($validated['contact_info'])) {
            $validated['contact_info'] = null;
        }

        $provider->update($validated);

        return redirect()->route('admin.providers.index')
            ->with('success', 'Provider updated successfully.');
    }

    public function destroy(Provider $provider)
    {
        $this->authorize('delete', $provider);
        
        // Check if provider has associated tariffs
        if ($provider->tariffs()->exists()) {
            return redirect()->route('admin.providers.index')
                ->with('error', 'Cannot delete provider with associated tariffs.');
        }

        $provider->delete();

        return redirect()->route('admin.providers.index')
            ->with('success', 'Provider deleted successfully.');
    }
}
