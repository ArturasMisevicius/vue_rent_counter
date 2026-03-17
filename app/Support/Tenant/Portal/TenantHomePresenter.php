<?php

namespace App\Support\Tenant\Portal;

use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Support\Collection;

class TenantHomePresenter
{
    public function __construct(
        protected PaymentInstructionsResolver $paymentInstructionsResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function for(User $tenant): array
    {
        $tenant = User::query()
            ->select(['id', 'name', 'organization_id'])
            ->with([
                'organization.settings:id,organization_id,payment_instructions,invoice_footer',
                'currentPropertyAssignment:id,property_id,tenant_user_id,assigned_at,unassigned_at',
                'currentPropertyAssignment.property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
                'currentPropertyAssignment.property.building:id,organization_id,name,address_line_1,address_line_2,city,postal_code,country_code',
                'currentPropertyAssignment.property.meters' => fn ($query) => $query
                    ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'type', 'status', 'unit'])
                    ->orderBy('name'),
                'currentPropertyAssignment.property.meters.latestReading:id,meter_id,reading_value,reading_date,validation_status',
                'invoices' => fn ($query) => $query
                    ->select([
                        'id',
                        'organization_id',
                        'property_id',
                        'tenant_user_id',
                        'invoice_number',
                        'status',
                        'currency',
                        'total_amount',
                        'amount_paid',
                        'due_date',
                        'billing_period_start',
                        'billing_period_end',
                    ])
                    ->orderByDesc('billing_period_start')
                    ->orderByDesc('id'),
            ])
            ->findOrFail($tenant->id);

        $property = $tenant->currentProperty;
        $meters = $property?->meters ?? Collection::make();
        $outstandingInvoices = $tenant->invoices->filter(fn (Invoice $invoice) => $invoice->amount_paid < $invoice->total_amount);
        $recentReadings = $property
            ? MeterReading::query()
                ->select(['id', 'meter_id', 'reading_value', 'reading_date'])
                ->where('property_id', $property->id)
                ->with('meter:id,identifier,name,unit')
                ->orderByDesc('reading_date')
                ->orderByDesc('id')
                ->limit(5)
                ->get()
            : Collection::make();

        $metersMissingCurrentMonth = $meters->filter(function ($meter): bool {
            $readingDate = $meter->latestReading?->reading_date;

            if ($readingDate === null) {
                return true;
            }

            return $readingDate->format('Y-m') !== now()->format('Y-m');
        })->count();

        return [
            'tenant_name' => $tenant->name,
            'property_name' => $property?->name,
            'property_address' => $property?->address,
            'property_url' => route('tenant.property.show'),
            'submit_reading_url' => route('tenant.readings.create'),
            'has_outstanding_balance' => $outstandingInvoices->isNotEmpty(),
            'outstanding_label' => $outstandingInvoices->isNotEmpty() ? 'Outstanding Balance' : 'All paid up',
            'outstanding_total' => $outstandingInvoices->sum(fn (Invoice $invoice) => (float) $invoice->total_amount - (float) $invoice->amount_paid),
            'outstanding_invoice_count' => $outstandingInvoices->count(),
            'payment_instructions' => $this->paymentInstructionsResolver->resolve($tenant->organization?->settings),
            'month_heading' => 'This Month',
            'meters_missing_current_month' => $metersMissingCurrentMonth,
            'current_month_message' => $metersMissingCurrentMonth > 0
                ? 'No reading this month'
                : 'All assigned meters have a current-month reading.',
            'recent_readings' => $recentReadings->map(fn (MeterReading $reading) => [
                'id' => $reading->id,
                'meter_identifier' => $reading->meter?->identifier,
                'meter_name' => $reading->meter?->name,
                'unit' => $reading->meter?->unit,
                'value' => number_format((float) $reading->reading_value, 3),
                'date' => $reading->reading_date->format('Y-m-d'),
            ])->all(),
        ];
    }
}
