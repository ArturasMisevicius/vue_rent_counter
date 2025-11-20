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
            ->with('success', 'Invoice created successfully.');
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice): \Illuminate\View\View
    {
        $invoice->load(['tenant', 'items']);
        return view('invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing an invoice.
     */
    public function edit(Invoice $invoice): \Illuminate\Http\RedirectResponse|\Illuminate\View\View
    {
        if (!$invoice->isDraft()) {
            return back()->with('error', 'Only draft invoices can be edited.');
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
            return back()->with('error', 'Only draft invoices can be updated.');
        }

        $validated = $request->validated();

        $invoice->update($validated);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully.');
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(Invoice $invoice): \Illuminate\Http\RedirectResponse
    {
        if (!$invoice->isDraft()) {
            return back()->with('error', 'Only draft invoices can be deleted.');
        }

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    /**
     * Finalize an invoice, making it immutable.
     */
    public function finalize(Invoice $invoice): \Illuminate\Http\RedirectResponse
    {
        if (!$invoice->isDraft()) {
            return back()->with('error', 'Invoice is already finalized.');
        }

        $invoice->finalize();

        return back()->with('success', 'Invoice finalized successfully.');
    }

    /**
     * Mark an invoice as paid.
     */
    public function markPaid(Invoice $invoice): \Illuminate\Http\RedirectResponse
    {
        if (!$invoice->isFinalized()) {
            return back()->with('error', 'Only finalized invoices can be marked as paid.');
        }

        $invoice->update(['status' => 'paid']);

        return back()->with('success', 'Invoice marked as paid.');
    }

    /**
     * Generate PDF for an invoice.
     */
    public function pdf(Invoice $invoice): \Illuminate\Http\JsonResponse
    {
        // Future: Generate PDF
        return response()->json(['message' => 'PDF generation not yet implemented']);
    }

    /**
     * Send invoice via email.
     */
    public function send(Invoice $invoice): \Illuminate\Http\RedirectResponse
    {
        if (!$invoice->isFinalized()) {
            return back()->with('error', 'Only finalized invoices can be sent.');
        }

        // Future: Send via email
        return back()->with('success', 'Invoice sent successfully.');
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

        return back()->with('success', "Generated {$count} invoices successfully.");
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
