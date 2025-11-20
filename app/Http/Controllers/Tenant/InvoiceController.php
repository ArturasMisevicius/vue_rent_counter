<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tenant = $user->tenant ?? null;
        
        if (!$tenant) {
            $invoices = collect();
            $properties = collect();
            return view('tenant.invoices.index', compact('invoices', 'properties'));
        }
        
        // Get all tenants with the same email (for multi-property support)
        $allTenants = \App\Models\Tenant::where('email', $user->email)->get();
        
        // Get all properties for these tenants (for multi-property filtering)
        $propertyIds = $allTenants->pluck('property_id')->unique()->filter();
        $properties = \App\Models\Property::whereIn('id', $propertyIds)->get();
        
        // Build invoice query for all tenants with this email
        $tenantIds = $allTenants->pluck('id');
        $invoicesQuery = \App\Models\Invoice::whereIn('tenant_renter_id', $tenantIds)
            ->with(['tenant.property']);
        
        // Apply property filter if provided
        if ($request->has('property_id') && $request->property_id) {
            $invoicesQuery->whereHas('tenant', function ($query) use ($request) {
                $query->where('property_id', $request->property_id);
            });
        }
        
        // Apply status filter
        if ($request->filled('status')) {
            $invoicesQuery->where('status', $request->input('status'));
        }
        
        // Apply date range filter
        if ($request->filled('from_date')) {
            $invoicesQuery->whereDate('billing_period_start', '>=', $request->input('from_date'));
        }
        
        if ($request->filled('to_date')) {
            $invoicesQuery->whereDate('billing_period_end', '<=', $request->input('to_date'));
        }
        
        // Handle sorting
        $sortColumn = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        
        // Validate sort column
        $allowedColumns = ['billing_period_start', 'billing_period_end', 'total_amount', 'status', 'created_at'];
        if (in_array($sortColumn, $allowedColumns)) {
            $invoicesQuery->orderBy($sortColumn, $sortDirection);
        } else {
            $invoicesQuery->latest();
        }
        
        $invoices = $invoicesQuery->paginate(20)->withQueryString();

        return view('tenant.invoices.index', compact('invoices', 'properties'));
    }

    public function show(Request $request, Invoice $invoice)
    {
        $user = $request->user();
        $tenant = $user->tenant ?? null;
        
        // Ensure tenant can only view their own invoices
        if (!$tenant || $invoice->tenant_renter_id !== $tenant->id) {
            abort(403);
        }

        $invoice->load(['items', 'tenant.property']);
        
        // Get consumption history for the billing period with eager loading
        $consumptionHistory = $tenant->meterReadings()
            ->with(['meter.property'])
            ->whereBetween('reading_date', [
                $invoice->billing_period_start,
                $invoice->billing_period_end
            ])
            ->orderBy('reading_date', 'asc')
            ->get();
        
        // Calculate consumption for each reading
        $consumptionHistory = $consumptionHistory->map(function ($reading) {
            $previousReading = \App\Models\MeterReading::where('meter_id', $reading->meter_id)
                ->where('reading_date', '<', $reading->reading_date)
                ->orderBy('reading_date', 'desc')
                ->first();
            
            $reading->consumption = $previousReading 
                ? $reading->value - $previousReading->value 
                : null;
            
            return $reading;
        });

        return view('tenant.invoices.show', compact('invoice', 'consumptionHistory'));
    }

    public function pdf(Request $request, Invoice $invoice)
    {
        $user = $request->user();
        $tenant = $user->tenant ?? null;
        
        if (!$tenant || $invoice->tenant_renter_id !== $tenant->id) {
            abort(403);
        }

        // Future: Generate PDF
        return response()->json(['message' => 'PDF generation not yet implemented']);
    }
}
