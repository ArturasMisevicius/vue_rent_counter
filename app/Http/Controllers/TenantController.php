<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::with('property')->paginate(20);
        return view('tenants.index', compact('tenants'));
    }

    public function create()
    {
        $properties = Property::all();
        return view('tenants.create', compact('properties'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'property_id' => ['required', 'exists:properties,id'],
            'lease_start' => ['required', 'date'],
            'lease_end' => ['nullable', 'date', 'after:lease_start'],
        ]);

        Tenant::create($validated);

        return redirect()->route('tenants.index')
            ->with('success', 'Tenant created successfully.');
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['property', 'invoices']);
        return view('tenants.show', compact('tenant'));
    }

    public function edit(Tenant $tenant)
    {
        $properties = Property::all();
        return view('tenants.edit', compact('tenant', 'properties'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'property_id' => ['required', 'exists:properties,id'],
            'lease_start' => ['required', 'date'],
            'lease_end' => ['nullable', 'date', 'after:lease_start'],
        ]);

        $tenant->update($validated);

        return redirect()->route('tenants.index')
            ->with('success', 'Tenant updated successfully.');
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();

        return redirect()->route('tenants.index')
            ->with('success', 'Tenant deleted successfully.');
    }

    public function invoices(Tenant $tenant)
    {
        $invoices = $tenant->invoices()->latest()->paginate(20);
        return view('tenants.invoices', compact('tenant', 'invoices'));
    }

    public function consumption(Tenant $tenant)
    {
        $readings = $tenant->meterReadings()
            ->with('meter')
            ->latest('reading_date')
            ->paginate(50);
        
        return view('tenants.consumption', compact('tenant', 'readings'));
    }

    public function sendInvoice(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'invoice_id' => ['required', 'exists:invoices,id'],
        ]);

        // Future: Send invoice via email
        return back()->with('success', 'Invoice sent successfully.');
    }
}
