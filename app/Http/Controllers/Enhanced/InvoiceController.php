<?php

declare(strict_types=1);

namespace App\Http\Controllers\Enhanced;

use App\DTOs\InvoiceGenerationDTO;
use App\DTOs\Enhanced\PaymentProcessingDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\ProcessPaymentRequest;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\Enhanced\BillingService;
use App\Actions\Enhanced\ProcessPaymentAction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Enhanced Invoice Controller
 * 
 * Thin controller that delegates business logic to services.
 * Handles only HTTP concerns: validation, responses, and routing.
 * 
 * @package App\Http\Controllers\Enhanced
 */
final class InvoiceController extends Controller
{
    public function __construct(
        private readonly BillingService $billingService,
        private readonly ProcessPaymentAction $processPaymentAction
    ) {}

    /**
     * Display a listing of invoices.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Invoice::class);

        // Simple query for display - no business logic
        $invoices = Invoice::with(['tenant.property', 'items'])
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(20);

        return view('invoices.index', compact('invoices'));
    }

    /**
     * Store a newly created invoice.
     */
    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $dto = InvoiceGenerationDTO::fromRequest($request);
        $result = $this->billingService->generateInvoice($dto);

        if ($result->success) {
            return redirect()
                ->route('invoices.show', $result->data)
                ->with('success', $result->message);
        }

        return back()
            ->withInput()
            ->withErrors(['error' => $result->message]);
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        // Load relationships for display
        $invoice->load(['tenant.property', 'items.serviceConfiguration.utilityService', 'payments']);

        // Get consumption history through service
        $consumptionResult = $this->billingService->calculateConsumption(
            $invoice->tenant->property,
            $invoice->billing_period_start,
            $invoice->billing_period_end
        );

        $consumptionHistory = $consumptionResult->success ? $consumptionResult->data : [];

        return view('invoices.show', compact('invoice', 'consumptionHistory'));
    }

    /**
     * Finalize an invoice.
     */
    public function finalize(Invoice $invoice): RedirectResponse
    {
        $result = $this->billingService->finalizeInvoice($invoice);

        if ($result->success) {
            return back()->with('success', $result->message);
        }

        return back()->withErrors(['error' => $result->message]);
    }

    /**
     * Process payment for an invoice.
     */
    public function processPayment(ProcessPaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('processPayment', $invoice);

        try {
            $dto = PaymentProcessingDTO::fromRequest($request);
            $payment = $this->processPaymentAction->execute($dto);

            return back()->with('success', 'Payment processed successfully');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Payment processing failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Generate bulk invoices.
     */
    public function generateBulk(Request $request): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $request->validate([
            'tenant_ids' => 'nullable|array',
            'tenant_ids.*' => 'exists:tenants,id',
            'billing_period_start' => 'required|date',
            'billing_period_end' => 'required|date|after:billing_period_start',
        ]);

        $tenants = isset($request->tenant_ids) 
            ? Tenant::whereIn('id', $request->tenant_ids)->get()
            : Tenant::all();

        $result = $this->billingService->generateBulkInvoices(
            $tenants,
            $request->date('billing_period_start'),
            $request->date('billing_period_end')
        );

        if ($result->success) {
            $data = $result->data;
            $message = "Bulk generation completed. Success: {$data['successful_count']}, Failed: {$data['failed_count']}";
            return back()->with('success', $message);
        }

        return back()->withErrors(['error' => $result->message]);
    }

    /**
     * Get billing history for API.
     */
    public function billingHistory(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'months' => 'nullable|integer|min:1|max:60',
        ]);

        $months = $request->input('months', 12);
        $result = $this->billingService->getBillingHistory($tenant, $months);

        if ($result->success) {
            return response()->json([
                'data' => $result->data,
                'message' => $result->message,
            ]);
        }

        return response()->json([
            'error' => $result->message,
        ], 400);
    }

    /**
     * Get consumption data for API.
     */
    public function consumptionData(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        $result = $this->billingService->calculateConsumption(
            $invoice->tenant->property,
            $invoice->billing_period_start,
            $invoice->billing_period_end
        );

        if ($result->success) {
            return response()->json([
                'data' => $result->data,
                'message' => $result->message,
            ]);
        }

        return response()->json([
            'error' => $result->message,
        ], 400);
    }
}