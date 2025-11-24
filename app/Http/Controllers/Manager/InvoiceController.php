<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\FinalizeInvoiceRequest;
use App\Http\Requests\ManagerMarkInvoicePaidRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        private BillingService $billingService
    ) {}

    /**
     * Display a listing of invoices with status filtering.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Invoice::class);

        $query = Invoice::with(['tenant.property', 'items']);

        // Filter by status if provided
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Handle sorting
        $sortColumn = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        
        // Validate sort column
        $allowedColumns = ['billing_period_start', 'billing_period_end', 'total_amount', 'status', 'created_at'];
        if (in_array($sortColumn, $allowedColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->latest();
        }

        $invoices = $query->paginate(20)->withQueryString();

        return view('manager.invoices.index', compact('invoices'));
    }

    /**
     * Show the form for creating/generating a new invoice.
     */
    public function create(): View
    {
        $this->authorize('create', Invoice::class);

        $tenants = Tenant::with('property')->orderBy('name')->get();

        return view('manager.invoices.create', compact('tenants'));
    }

    /**
     * Store/generate a newly created invoice.
     */
    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $validated = $request->validated();

        $tenant = Tenant::findOrFail($validated['tenant_renter_id']);
        
        $invoice = $this->billingService->generateInvoice(
            $tenant,
            $validated['billing_period_start'],
            $validated['billing_period_end']
        );

        return redirect()
            ->route('manager.invoices.show', $invoice)
            ->with('success', __('notifications.invoice.created'));
    }

    /**
     * Display the specified invoice with edit capability for drafts.
     */
    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice->load(['tenant.property', 'items']);

        return view('manager.invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing a draft invoice.
     */
    public function edit(Invoice $invoice): View|RedirectResponse
    {
        $this->authorize('update', $invoice);

        if (!$invoice->isDraft()) {
            return back()->with('error', __('invoices.errors.edit_draft_only'));
        }

        $tenants = Tenant::with('property')->orderBy('name')->get();

        return view('manager.invoices.edit', compact('invoice', 'tenants'));
    }

    /**
     * Update the specified draft invoice.
     */
    public function update(StoreInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if (!$invoice->isDraft()) {
            return back()->with('error', __('invoices.errors.update_draft_only'));
        }

        $validated = $request->validated();
        
        // If billing period or tenant changed, regenerate the invoice
        $periodChanged = $invoice->billing_period_start->format('Y-m-d') !== $validated['billing_period_start']
            || $invoice->billing_period_end->format('Y-m-d') !== $validated['billing_period_end'];
        $tenantChanged = $invoice->tenant_renter_id !== $validated['tenant_renter_id'];

        if ($periodChanged || $tenantChanged) {
            // Delete existing items
            $invoice->items()->delete();
            
            // Update invoice with new data
            $invoice->update($validated);
            
            // Regenerate items
            $tenant = Tenant::findOrFail($validated['tenant_renter_id']);
            $newInvoice = $this->billingService->generateInvoice(
                $tenant,
                $validated['billing_period_start'],
                $validated['billing_period_end']
            );
            
            // Copy items from new invoice to existing invoice
            foreach ($newInvoice->items as $item) {
                $invoice->items()->create($item->toArray());
            }
            
            // Update total
            $invoice->update(['total_amount' => $newInvoice->total_amount]);
            
            // Delete the temporary invoice
            $newInvoice->items()->delete();
            $newInvoice->delete();
        } else {
            $invoice->update($validated);
        }

        return redirect()
            ->route('manager.invoices.show', $invoice)
            ->with('success', __('notifications.invoice.updated'));
    }

    /**
     * Remove the specified draft invoice.
     */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        if (!$invoice->isDraft()) {
            return back()->with('error', __('invoices.errors.delete_draft_only'));
        }

        $invoice->delete();

        return redirect()
            ->route('manager.invoices.index')
            ->with('success', __('notifications.invoice.deleted'));
    }

    /**
     * Finalize an invoice with snapshotting.
     */
    public function finalize(FinalizeInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if (!$invoice->isDraft()) {
            return back()->with('error', __('invoices.errors.already_finalized'));
        }

        $invoice->finalize();

        return back()->with('success', __('notifications.invoice.finalized_locked'));
    }

    /**
     * Display draft invoices.
     */
    public function drafts(): View
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::draft()
            ->with(['tenant.property', 'items'])
            ->latest()
            ->paginate(20);

        return view('manager.invoices.drafts', compact('invoices'));
    }

    /**
     * Display finalized invoices.
     */
    public function finalized(): View
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::finalized()
            ->with(['tenant.property', 'items'])
            ->latest()
            ->paginate(20);

        return view('manager.invoices.finalized', compact('invoices'));
    }

    /**
     * Mark an invoice as paid.
     */
    public function markPaid(ManagerMarkInvoicePaidRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if ($invoice->isPaid()) {
            return back()->with('success', __('notifications.invoice.already_paid') ?? 'Invoice already marked as paid.');
        }

        $validated = $request->validated();

        $invoice->status = \App\Enums\InvoiceStatus::PAID;
        $invoice->paid_at = !empty($validated['paid_at'])
            ? \Carbon\Carbon::parse($validated['paid_at'])
            : now();

        if (! empty($validated['payment_reference'])) {
            $invoice->payment_reference = $validated['payment_reference'];
        }

        if (array_key_exists('paid_amount', $validated)) {
            $invoice->paid_amount = $validated['paid_amount'] ?? $invoice->total_amount;
        } elseif ($invoice->paid_amount === null) {
            $invoice->paid_amount = $invoice->total_amount;
        }

        $invoice->save();

        return back()->with('success', __('notifications.invoice.marked_paid') ?? 'Invoice marked as paid.');
    }
}
