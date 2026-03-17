<?php

namespace App\Filament\Support\Tenant\Portal;

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
            ->withTenantWorkspaceSummary($organizationId)
            ->with([
                'organization.settings:id,organization_id,billing_contact_name,billing_contact_email,billing_contact_phone,payment_instructions,invoice_footer',
                'currentPropertyAssignment.property.meters' => fn ($query) => $query
                    ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'type', 'status', 'unit'])
                    ->forOrganization($organizationId)
                    ->orderBy('name'),
                'currentPropertyAssignment.property.meters.latestReading' => fn ($query) => $query
                    ->select(['id', 'organization_id', 'meter_id', 'reading_value', 'reading_date', 'validation_status'])
                    ->forOrganization($organizationId)
                    ->latestFirst(),
            ])
            ->findOrFail($tenantId);

        $property = $tenant->currentProperty;
        $meters = $property?->meters ?? Collection::make();
        $outstandingInvoices = Invoice::query()
            ->forTenantWorkspace($organizationId, $tenantId)
            ->outstanding()
            ->get();
        $outstandingTotal = $outstandingInvoices->sum(
            fn (Invoice $invoice) => $invoice->outstanding_balance
        );
        $outstandingCurrency = (string) ($outstandingInvoices->first()?->currency ?? '');
        $recentReadings = $property
            ? MeterReading::query()
                ->select(['id', 'meter_id', 'reading_value', 'reading_date'])
                ->forOrganization($organizationId)
                ->forProperty($property->id)
                ->with('meter:id,identifier,name,unit')
                ->latestFirst()
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
