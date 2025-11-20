@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('invoices.index') }}" class="text-blue-600 hover:text-blue-800">
            ← Back to Invoices
        </a>
    </div>

    <x-invoice-summary :invoice="$invoice" />

    <div class="mt-6 flex gap-4">
        @if($invoice->isDraft())
            <form action="{{ route('invoices.finalize', $invoice) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Finalize Invoice
                </button>
            </form>
            <a href="{{ route('invoices.edit', $invoice) }}" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                Edit Invoice
            </a>
            <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this invoice?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                    Delete Invoice
                </button>
            </form>
        @elseif($invoice->isFinalized())
            <form action="{{ route('invoices.mark-paid', $invoice) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    Mark as Paid
                </button>
            </form>
            <a href="{{ route('invoices.pdf', $invoice) }}" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                Download PDF
            </a>
            <form action="{{ route('invoices.send', $invoice) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Send to Tenant
                </button>
            </form>
        @endif
    </div>
</div>
@endsection
