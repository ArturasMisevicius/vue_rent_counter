@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('tenant.invoices.index') }}" class="text-blue-600 hover:text-blue-800">
            ← Back to My Invoices
        </a>
    </div>

    <x-invoice-summary 
        :invoice="$invoice" 
        :consumption-history="$consumptionHistory ?? collect()" 
    />

    <div class="mt-6 flex gap-4">
        @if($invoice->isFinalized() || $invoice->isPaid())
            <a href="{{ route('tenant.invoices.pdf', $invoice) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Download PDF
            </a>
        @endif
    </div>
</div>
@endsection
