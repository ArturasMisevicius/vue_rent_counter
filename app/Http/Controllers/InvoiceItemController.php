<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;

class InvoiceItemController extends Controller
{
    public function index(Invoice $invoice)
    {
        $items = $invoice->items;
        return view('invoices.items.index', compact('invoice', 'items'));
    }

    public function store(Request $request, Invoice $invoice)
    {
        if (!$invoice->isDraft()) {
            return back()->with('error', 'Cannot add items to finalized invoice.');
        }

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'total_price' => ['required', 'numeric', 'min:0'],
        ]);

        $validated['tenant_id'] = $invoice->tenant_id;
        $validated['invoice_id'] = $invoice->id;

        $invoice->items()->create($validated);

        // Recalculate invoice total
        $invoice->total_amount = $invoice->items()->sum('total_price');
        $invoice->save();

        return back()->with('success', 'Invoice item added successfully.');
    }

    public function show(Invoice $invoice, InvoiceItem $item)
    {
        if ($item->invoice_id !== $invoice->id) {
            abort(404);
        }

        return view('invoices.items.show', compact('invoice', 'item'));
    }

    public function update(Request $request, Invoice $invoice, InvoiceItem $item)
    {
        if (!$invoice->isDraft()) {
            return back()->with('error', 'Cannot update items in finalized invoice.');
        }

        if ($item->invoice_id !== $invoice->id) {
            abort(404);
        }

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'total_price' => ['required', 'numeric', 'min:0'],
        ]);

        $item->update($validated);

        // Recalculate invoice total
        $invoice->total_amount = $invoice->items()->sum('total_price');
        $invoice->save();

        return back()->with('success', 'Invoice item updated successfully.');
    }

    public function destroy(Invoice $invoice, InvoiceItem $item)
    {
        if (!$invoice->isDraft()) {
            return back()->with('error', 'Cannot delete items from finalized invoice.');
        }

        if ($item->invoice_id !== $invoice->id) {
            abort(404);
        }

        $item->delete();

        // Recalculate invoice total
        $invoice->total_amount = $invoice->items()->sum('total_price');
        $invoice->save();

        return back()->with('success', 'Invoice item deleted successfully.');
    }
}
