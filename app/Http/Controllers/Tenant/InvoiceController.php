<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\MeterReading;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Get assigned property from hierarchical user model
        $property = $user->property;

        if (! $property) {
            $invoices = collect();
            $properties = collect();
            $invoiceStatusLabels = \App\Enums\InvoiceStatus::labels();
            $statusStyles = [
                'draft' => 'bg-amber-100 text-amber-800 ring-1 ring-amber-600/30',
                'finalized' => 'bg-sky-100 text-sky-800 ring-1 ring-sky-600/30',
                'paid' => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-600/30',
            ];

            return view('pages.invoices.index', compact('invoices', 'properties', 'invoiceStatusLabels', 'statusStyles'));
        }

        // Get tenant record for invoice lookup (legacy compatibility)
        $tenant = $user->tenant;

        if (! $tenant) {
            $invoices = collect();
            $properties = collect([$property]);
            $invoiceStatusLabels = \App\Enums\InvoiceStatus::labels();
            $statusStyles = [
                'draft' => 'bg-amber-100 text-amber-800 ring-1 ring-amber-600/30',
                'finalized' => 'bg-sky-100 text-sky-800 ring-1 ring-sky-600/30',
                'paid' => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-600/30',
            ];

            return view('pages.invoices.index', compact('invoices', 'properties', 'invoiceStatusLabels', 'statusStyles'));
        }

        // Build invoice query filtered to assigned property
        $invoicesQuery = Invoice::where('tenant_renter_id', $tenant->id)
            ->with(['tenant.property', 'items']);

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
        $properties = collect([$property]);

        // Pass invoice status labels and styles to view
        $invoiceStatusLabels = \App\Enums\InvoiceStatus::labels();
        $statusStyles = [
            'draft' => 'bg-amber-100 text-amber-800 ring-1 ring-amber-600/30',
            'finalized' => 'bg-sky-100 text-sky-800 ring-1 ring-sky-600/30',
            'paid' => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-600/30',
        ];

        return view('pages.invoices.index', compact('invoices', 'properties', 'invoiceStatusLabels', 'statusStyles'));
    }

    public function show(Request $request, Invoice $invoice)
    {
        $user = $request->user();

        // Get assigned property from hierarchical user model
        $property = $user->property;

        // Get tenant record for invoice lookup (legacy compatibility)
        $tenant = $user->tenant;

        // Verify property_id filtering - tenant can only view invoices for their assigned property
        if (! $tenant || $invoice->tenant_renter_id !== $tenant->id) {
            abort(404);
        }

        // Additional check: ensure invoice is for the assigned property
        if ($invoice->tenant && $invoice->tenant->property_id !== $property?->id) {
            abort(404);
        }

        $invoice->load(['items', 'tenant.property']);

        // Get consumption history for the billing period with property filtering
        $consumptionHistory = MeterReading::whereHas('meter', function ($query) use ($property) {
            $query->where('property_id', $property->id);
        })
            ->with(['meter'])
            ->whereBetween('reading_date', [
                $invoice->billing_period_start,
                $invoice->billing_period_end,
            ])
            ->orderBy('reading_date', 'asc')
            ->get();

        // Calculate consumption for each reading
        $consumptionHistory = $consumptionHistory->map(function ($reading) {
            $previousReading = MeterReading::where('meter_id', $reading->meter_id)
                ->where('reading_date', '<', $reading->reading_date)
                ->orderBy('reading_date', 'desc')
                ->first();

            $reading->consumption = $previousReading
                ? $reading->value - $previousReading->value
                : null;

            return $reading;
        });

        return view('pages.invoices.show', compact('invoice', 'consumptionHistory'));
    }

    public function pdf(Request $request, Invoice $invoice)
    {
        $user = $request->user();

        // Get assigned property from hierarchical user model
        $property = $user->property;

        // Get tenant record for invoice lookup (legacy compatibility)
        $tenant = $user->tenant;

        // Verify property_id filtering - tenant can only download PDFs for their assigned property
        if (! $tenant || $invoice->tenant_renter_id !== $tenant->id) {
            abort(404);
        }

        // Additional check: ensure invoice is for the assigned property
        if ($invoice->tenant && $invoice->tenant->property_id !== $property?->id) {
            abort(404);
        }

        // Render receipt view; for now, return HTML until PDF generation is wired
        $invoice->load(['items', 'tenant.property']);

        return view('pages.invoices.receipt', compact('invoice'));
    }
}
