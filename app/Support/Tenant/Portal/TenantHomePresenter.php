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
        $tenantId = $tenant->id;
        $organizationId = $tenant->organization_id;

        $tenant = User::query()
            ->select(['id', 'name', 'organization_id'])
            ->with([
                'organization.settings:id,organization_id,billing_contact_name,billing_contact_email,billing_contact_phone,payment_instructions,invoice_footer',
                'currentPropertyAssignment' => fn ($query) => $query
                    ->select(['id', 'organization_id', 'property_id', 'tenant_user_id', 'assigned_at', 'unassigned_at'])
                    ->where('organization_id', $organizationId)
                    ->whereNull('unassigned_at'),
                'currentPropertyAssignment.property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
                'currentPropertyAssignment.property.building:id,organization_id,name,address_line_1,address_line_2,city,postal_code,country_code',
                'currentPropertyAssignment.property.meters' => fn ($query) => $query
                    ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'type', 'status', 'unit'])
                    ->where('organization_id', $organizationId)
                    ->orderBy('name'),
                'currentPropertyAssignment.property.meters.latestReading' => fn ($query) => $query
                    ->select(['id', 'organization_id', 'meter_id', 'reading_value', 'reading_date', 'validation_status'])
                    ->where('organization_id', $organizationId),
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
                    ->where('organization_id', $organizationId)
                    ->orderByDesc('billing_period_start')
                    ->orderByDesc('id'),
            ])
            ->findOrFail($tenantId);

        $property = $tenant->currentProperty;
        $meters = $property?->meters ?? Collection::make();
        $outstandingInvoices = $tenant->invoices->filter(fn (Invoice $invoice) => $invoice->amount_paid < $invoice->total_amount);
        $outstandingTotal = $outstandingInvoices->sum(
            fn (Invoice $invoice) => (float) $invoice->total_amount - (float) $invoice->amount_paid
        );
        $outstandingCurrency = (string) ($outstandingInvoices->first()?->currency ?? '');
        $recentReadings = $property
            ? MeterReading::query()
                ->select(['id', 'meter_id', 'reading_value', 'reading_date'])
                ->where('organization_id', $organizationId)
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
            'outstanding_label' => $outstandingInvoices->isNotEmpty()
                ? __('tenant.status.outstanding_balance')
                : __('tenant.status.all_paid_up'),
            'outstanding_total' => $outstandingTotal,
            'outstanding_total_display' => trim($outstandingCurrency.' '.number_format($outstandingTotal, 2)),
            'outstanding_invoice_count' => $outstandingInvoices->count(),
            'payment_guidance' => $this->paymentInstructionsResolver->resolve($tenant->organization?->settings),
            'month_heading' => __('tenant.pages.home.month_heading'),
            'meters_missing_current_month' => $metersMissingCurrentMonth,
            'current_month_metric' => trans_choice('tenant.pages.home.current_month_metric', $metersMissingCurrentMonth, [
                'count' => $metersMissingCurrentMonth,
            ]),
            'current_month_message' => $metersMissingCurrentMonth > 0
                ? __('tenant.pages.home.no_reading_this_month')
                : __('tenant.messages.all_current_month'),
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
