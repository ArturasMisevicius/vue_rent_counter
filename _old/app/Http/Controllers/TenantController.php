<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendTenantInvoiceRequest;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Models\Property;
use App\Models\Tenant;

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

    public function store(StoreTenantRequest $request)
    {
        $validated = $request->validated();

        Tenant::create($validated);

        return redirect()->route('tenants.index')
            ->with('success', __('notifications.tenant.created'));
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

    public function update(UpdateTenantRequest $request, Tenant $tenant)
    {
        $validated = $request->validated();

        $tenant->update($validated);

        return redirect()->route('tenants.index')
            ->with('success', __('notifications.tenant.updated'));
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();

        return redirect()->route('tenants.index')
            ->with('success', __('notifications.tenant.deleted'));
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

    public function sendInvoice(SendTenantInvoiceRequest $request, Tenant $tenant)
    {
        // Future: Send invoice via email
        return back()->with('success', __('notifications.tenant.invoice_sent'));
    }
}
