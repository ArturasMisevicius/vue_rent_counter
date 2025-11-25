<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateBulkInvoicesRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\BillingService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private BillingService $billingService
    ) {}

    /**
     * Display a listing of invoices.
     */
    public function index(): \Illuminate\View\View
    {
        $invoices = Invoice::with(['tenant.property', 'items'])
            ->latest()
            ->paginate(20);
        return view('invoices.index', compact('invoices'));
    }

    /**
     * Show the form for creating a new invoice.
     */
    public function create(): \Illuminate\View\View
    {
        $tenants = Tenant::with('property')->get();
        return view('invoices.create', compact('tenants'));
    }

    /**
     * Store a newly created invoice.
     */
    public function store(StoreInvoiceRequest $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validated();

        $tenant = Tenant::findOrFail($validated['tenant_renter_id']);
        
        $invoice = $this->billingService->generateInvoice(
            $tenant,
            $validated['billing_period_start'],
            $validated['billing_period_end']
        );

        return redirect()->route('invoices.show', $invoice)
            ->with('success', __('notifications.invoice.created'));
    }

    /**
     * Display the specified invoice.
     * 
     * Requirements:
     * - 6.2: Display itemized breakdown by utility type
     * - 6.3: Display chronologically ordered consumption history
     * - 6.4: Show consumption amount and rate applied for each item
     */
    public function show(Invoice $invoice): \Illuminate\View\View
    {
        $invoice->load(['tenant.property', 'items']);
        
        // Get consumption history for the billing period (Requirement 6.3)
        $consumptionHistory = collect();
        if ($invoice->tenant && $invoice->tenant->property) {
            $consumptionHistory = \App\Models\MeterReading::whereHas('meter', function ($query) use ($invoice) {
                $query->where('property_id', $invoice->tenant->property_id);
            })
            ->with(['meter'])
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
        }
        
        return view('invoices.show', compact('invoice', 'consumptionHistory'));
    }

    /**
     * Show the form for editing an invoice.
     */
    public function edit(Invoice $invoice): \Illuminate\Http\RedirectResponse|\Illuminate\View\View
    {
        if (!$invoice->isDraft()) {
            return back()->with('error', __('invoices.errors.edit_draft_only'));
        }

        $tenants = Tenant::with('property')->get();
        return view('invoices.edit', compact('invoice', 'tenants'));
    }

    /**
     * Update the specified invoice.
     */
    public function update(StoreInvoiceRequest $request, Invoice $invoice): \Illuminate\Http\RedirectResponse
    {
        if (!$invoice->isDraft()) {
            return back()->with('error', __('invoices.errors.update_draft_only'));
        }

        $validated = $request->validated();

        $invoice->update($validated);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', __('notifications.invoice.updated'));
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(Invoice $invoice): \Illuminate\Http\RedirectResponse
    {
        if (!$invoice->isDraft()) {
            return back()->with('error', __('invoices.errors.delete_draft_only'));
        }

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', __('notifications.invoice.deleted'));
    }

    /**
     * Finalize an invoice, making it immutable.
     */
    public function finalize(Invoice $invoice): \Illuminate\Http\RedirectResponse
    {
        if (!$invoice->isDraft()) {
            return back()->with('error', __('invoices.errors.already_finalized'));
        }

        $invoice->finalize();

        return back()->with('success', __('notifications.invoice.finalized'));
    }

    /**
     * Mark an invoice as paid.
     */
    public function markPaid(Invoice $invoice): \Illuminate\Http\RedirectResponse
    {
        if (!$invoice->isFinalized()) {
            return back()->with('error', __('invoices.errors.mark_paid_finalized'));
        }

        $invoice->update(['status' => 'paid']);

        return back()->with('success', __('notifications.invoice.marked_paid'));
    }

    /**
     * Generate PDF for an invoice.
     */
    public function pdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['items', 'tenant.property']);

        // For now return HTML receipt; can be hooked into a PDF generator later.
        return view('invoices.receipt', compact('invoice'));
    }

    /**
     * Send invoice via email.
     */
    public function send(Invoice $invoice): \Illuminate\Http\RedirectResponse
    {
        if (!$invoice->isFinalized()) {
            return back()->with('error', __('invoices.errors.send_finalized_only'));
        }

        // Future: Send via email
        return back()->with('success', __('notifications.invoice.sent'));
    }

    /**
     * Generate invoices for multiple tenants.
     */
    public function generateBulk(GenerateBulkInvoicesRequest $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validated();

        $tenants = isset($validated['tenant_ids']) 
            ? Tenant::whereIn('id', $validated['tenant_ids'])->get()
            : Tenant::all();

        $count = 0;
        foreach ($tenants as $tenant) {
            $this->billingService->generateInvoice(
                $tenant,
                $validated['billing_period_start'],
                $validated['billing_period_end']
            );
            $count++;
        }

        return back()->with('success', __('notifications.invoice.generated_bulk', ['count' => $count]));
    }

    /**
     * Display draft invoices.
     */
    public function drafts(): \Illuminate\View\View
    {
        $invoices = Invoice::draft()
            ->with(['tenant.property', 'items'])
            ->latest()
            ->paginate(20);
        return view('invoices.drafts', compact('invoices'));
    }

    /**
     * Display finalized invoices.
     */
    public function finalized(): \Illuminate\View\View
    {
        $invoices = Invoice::finalized()
            ->with(['tenant.property', 'items'])
            ->latest()
            ->paginate(20);
        return view('invoices.finalized', compact('invoices'));
    }

    /**
     * Display paid invoices.
     */
    public function paid(): \Illuminate\View\View
    {
        $invoices = Invoice::paid()
            ->with(['tenant.property', 'items'])
            ->latest()
            ->paginate(20);
        return view('invoices.paid', compact('invoices'));
    }
}
