<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('invoices.pdf.document_title', ['number' => $invoice->invoice_number]) }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.6;
        }

        .container {
            padding: 20px;
            max-width: 100%;
        }

        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
        }

        .header h1 {
            color: #2563eb;
            font-size: 24pt;
            margin-bottom: 5px;
        }

        .header .invoice-number {
            font-size: 12pt;
            color: #666;
        }

        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .info-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .info-block {
            margin-bottom: 15px;
        }

        .info-block h3 {
            font-size: 11pt;
            color: #2563eb;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .info-block p {
            margin: 2px 0;
            color: #555;
        }

        .info-block .label {
            font-weight: bold;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table thead {
            background-color: #2563eb;
            color: white;
        }

        table th {
            padding: 10px;
            text-align: left;
            font-weight: bold;
            font-size: 10pt;
        }

        table th.text-right {
            text-align: right;
        }

        table tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }

        table tbody tr:hover {
            background-color: #f9fafb;
        }

        table td {
            padding: 10px;
            font-size: 9pt;
        }

        table td.text-right {
            text-align: right;
        }

        .totals {
            margin-left: auto;
            width: 300px;
            margin-top: 20px;
        }

        .totals-row {
            display: table;
            width: 100%;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .totals-row.total {
            border-top: 2px solid #2563eb;
            border-bottom: 2px solid #2563eb;
            font-size: 14pt;
            font-weight: bold;
            color: #2563eb;
            margin-top: 10px;
        }

        .totals-label {
            display: table-cell;
            width: 60%;
            text-align: right;
            padding-right: 15px;
            font-weight: bold;
        }

        .totals-value {
            display: table-cell;
            width: 40%;
            text-align: right;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 4px;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-draft {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-finalized {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-paid {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-overdue {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 8pt;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <h1>{{ __('invoices.pdf.heading') }}</h1>
            <div class="invoice-number">
                {{ $invoice->invoice_number ?? 'INV-' . $invoice->id }}
                @if($invoice->status)
                    <span class="status-badge status-{{ strtolower($invoice->status->value) }}">
                        {{ enum_label($invoice->status) }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Invoice Information --}}
        <div class="info-section">
            <div class="info-column">
                <div class="info-block">
                    <h3>{{ __('invoices.pdf.tenant_information') }}</h3>
                    @if($invoice->tenantRenter)
                        <p><span class="label">{{ __('invoices.pdf.labels.name') }}:</span> {{ $invoice->tenantRenter->name }}</p>
                        <p><span class="label">{{ __('invoices.pdf.labels.email') }}:</span> {{ $invoice->tenantRenter->email }}</p>
                        @if($invoice->tenantRenter->phone)
                            <p><span class="label">{{ __('invoices.pdf.labels.phone') }}:</span> {{ $invoice->tenantRenter->phone }}</p>
                        @endif
                    @endif

                    @if($invoice->tenant && $invoice->tenant->property)
                        <p><span class="label">{{ __('invoices.pdf.labels.property') }}:</span>
                            {{ $invoice->tenant->property->unit_number
                                ? __('invoices.pdf.property_unit', ['unit' => $invoice->tenant->property->unit_number])
                                : __('invoices.pdf.property_id', ['id' => $invoice->tenant->property->id]) }}
                        </p>
                        @if($invoice->tenant->property->address)
                            <p><span class="label">{{ __('invoices.pdf.labels.address') }}:</span> {{ $invoice->tenant->property->address }}</p>
                        @endif
                    @endif
                </div>
            </div>

            <div class="info-column">
                <div class="info-block">
                    <h3>{{ __('invoices.pdf.invoice_details') }}</h3>
                    <p><span class="label">{{ __('invoices.pdf.labels.issue_date') }}:</span> {{ $invoice->created_at->format('Y-m-d') }}</p>
                    <p><span class="label">{{ __('invoices.pdf.labels.billing_period') }}:</span> {{ $invoice->billing_period_start->format('Y-m-d') }} {{ __('invoices.pdf.to') }} {{ $invoice->billing_period_end->format('Y-m-d') }}</p>
                    @if($invoice->due_date)
                        <p><span class="label">{{ __('invoices.pdf.labels.due_date') }}:</span> {{ $invoice->due_date->format('Y-m-d') }}</p>
                    @endif
                    @if($invoice->finalized_at)
                        <p><span class="label">{{ __('invoices.pdf.labels.finalized') }}:</span> {{ $invoice->finalized_at->format('Y-m-d H:i') }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Invoice Items Table --}}
        <table>
            <thead>
                <tr>
                    <th>{{ __('invoices.pdf.table.description') }}</th>
                    <th class="text-right">{{ __('invoices.pdf.table.quantity') }}</th>
                    <th>{{ __('invoices.pdf.table.unit') }}</th>
                    <th class="text-right">{{ __('invoices.pdf.table.unit_price') }}</th>
                    <th class="text-right">{{ __('invoices.pdf.table.total') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                        <td>{{ $item->unit ?? __('invoices.pdf.table.empty_unit') }}</td>
                        <td class="text-right">€{{ number_format($item->unit_price, 4) }}</td>
                        <td class="text-right">€{{ number_format($item->total, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: #9ca3af;">{{ __('invoices.pdf.table.no_items') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="totals">
            <div class="totals-row total">
                <div class="totals-label">{{ __('invoices.pdf.total') }}</div>
                <div class="totals-value">€{{ number_format($invoice->total_amount, 2) }}</div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>{{ __('invoices.pdf.generated_on', ['date' => now()->locale(app()->getLocale())->translatedFormat('Y-m-d H:i:s')]) }}</p>
            <p>{{ __('invoices.pdf.auto_generated_notice') }}</p>
        </div>
    </div>
</body>
</html>
