<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceItemRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;

class InvoiceItemController extends Controller
{
    public function index(Invoice $invoice)
    {
        $items = $invoice->items;
        return view('invoices.items.index', compact('invoice', 'items'));
    }

    public function store(InvoiceItemRequest $request, Invoice $invoice)
    {
        if (!$invoice->isDraft()) {
            return back()->with('error', __('invoices.errors.add_to_finalized'));
        }

        $validated = $request->validated();

        $validated['tenant_id'] = $invoice->tenant_id;
        $validated['invoice_id'] = $invoice->id;

        $invoice->items()->create($validated);

        // Recalculate invoice total
        $invoice->total_amount = $invoice->items()->sum('total_price');
        $invoice->save();

        return back()->with('success', __('notifications.invoice_item.created'));
    }

    public function show(Invoice $invoice, InvoiceItem $item)
    {
        if ($item->invoice_id !== $invoice->id) {
            abort(404);
        }

        return view('invoices.items.show', compact('invoice', 'item'));
    }

    public function update(InvoiceItemRequest $request, Invoice $invoice, InvoiceItem $item)
    {
        if (!$invoice->isDraft()) {
            return back()->with('error', __('invoices.errors.update_finalized'));
        }

        if ($item->invoice_id !== $invoice->id) {
            abort(404);
        }

        $validated = $request->validated();

        $item->update($validated);

        // Recalculate invoice total
        $invoice->total_amount = $invoice->items()->sum('total_price');
        $invoice->save();

        return back()->with('success', __('notifications.invoice_item.updated'));
    }

    public function destroy(Invoice $invoice, InvoiceItem $item)
    {
        if (!$invoice->isDraft()) {
            return back()->with('error', __('invoices.errors.delete_finalized'));
        }

        if ($item->invoice_id !== $invoice->id) {
            abort(404);
        }

        $item->delete();

        // Recalculate invoice total
        $invoice->total_amount = $invoice->items()->sum('total_price');
        $invoice->save();

        return back()->with('success', __('notifications.invoice_item.deleted'));
    }
}
