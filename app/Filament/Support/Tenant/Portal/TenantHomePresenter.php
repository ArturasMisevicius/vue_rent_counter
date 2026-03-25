<?php

namespace App\Filament\Support\Tenant\Portal;

use App\Filament\Support\Dashboard\DashboardCacheService;
use App\Filament\Support\Workspace\WorkspaceResolver;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Support\Collection;

class TenantHomePresenter
{
    public function __construct(
        protected PaymentInstructionsResolver $paymentInstructionsResolver,
        protected DashboardCacheService $dashboardCacheService,
        protected WorkspaceResolver $workspaceResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function for(User $tenant): array
    {
        return $this->dashboardCacheService->remember(
            $tenant,
            'tenant-home-summary',
            fn (): array => $this->buildSummary($tenant),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSummary(User $tenant): array
    {
        $workspace = $this->workspaceResolver->resolveFor($tenant);

        if (! $workspace->isTenant() || $workspace->organizationId === null) {
            return [
                'has_assignment' => false,
            ];
        }

        $tenantId = $workspace->userId;
        $organizationId = $workspace->organizationId;
        $propertyId = $workspace->propertyId;

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

        if ($propertyId !== null && $property?->id !== $propertyId) {
            $property = null;
        }

        $meters = $property?->meters ?? Collection::make();
        $hasAssignment = $property !== null;
        $outstandingInvoices = $property
            ? Invoice::query()
                ->forTenantWorkspace($organizationId, $tenantId)
                ->forProperty($property->id)
                ->outstanding()
                ->get()
            : Collection::make();
        $outstandingTotal = $outstandingInvoices->sum(
            fn (Invoice $invoice) => $invoice->outstanding_balance
        );
        $outstandingCurrency = (string) ($outstandingInvoices->first()?->currency ?? '');
        $recentReadings = $property
            ? MeterReading::query()
                ->select(['id', 'meter_id', 'reading_value', 'reading_date'])
                ->forOrganization($organizationId)
                ->forProperty($property->id)
                ->comparable()
                ->with('meter:id,identifier,name,unit,type')
                ->latestFirst()
                ->limit(3)
                ->get()
            : Collection::make();

        $consumptionByType = $property
            ? $this->buildConsumptionByType($organizationId, $property->id, $meters)
            : [];

        $metersMissingCurrentMonth = $meters->filter(function ($meter): bool {
            $readingDate = $meter->latestReading?->reading_date;

            if ($readingDate === null) {
                return true;
            }

            return $readingDate->format('Y-m') !== now()->format('Y-m');
        })->count();

        return [
            'tenant_name' => $tenant->name,
            'has_assignment' => $hasAssignment,
            'property_name' => $property?->name,
            'property_building_name' => $property?->building?->name,
            'property_address' => $property?->address,
            'assigned_property' => [
                'name' => $property?->name,
                'building' => $property?->building?->name,
                'address' => $property?->address,
            ],
            'property_url' => route('filament.admin.pages.tenant-property-details'),
            'submit_reading_url' => route('filament.admin.pages.tenant-submit-meter-reading'),
            'has_outstanding_balance' => $outstandingInvoices->isNotEmpty(),
            'outstanding_label' => $outstandingInvoices->isNotEmpty()
                ? __('tenant.status.outstanding_balance')
                : __('tenant.status.all_paid_up'),
            'outstanding_total' => $outstandingTotal,
            'outstanding_total_display' => $this->formatCurrency($outstandingTotal, $outstandingCurrency),
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
            'consumption_by_type' => $consumptionByType,
            'empty_state_title' => __('tenant.pages.home.unassigned_title'),
            'empty_state_description' => __('tenant.pages.home.unassigned_description'),
            'recent_readings' => $recentReadings->map(fn (MeterReading $reading) => [
                'id' => $reading->id,
                'meter_identifier' => $reading->meter?->identifier,
                'meter_name' => $reading->meter?->name,
                'meter_type' => $reading->meter?->type?->label(),
                'unit' => $reading->meter?->unit,
                'value' => $this->formatDecimal((float) $reading->reading_value, 3),
                'date' => $reading->reading_date->locale(app()->getLocale())->isoFormat('ll'),
            ])->all(),
        ];
    }

    /**
     * @param  Collection<int, Meter>  $meters
     * @return array<int, array{type: string, unit: string, value: string, display: string}>
     */
    private function buildConsumptionByType(int $organizationId, int $propertyId, Collection $meters): array
    {
        if ($meters->isEmpty()) {
            return [];
        }

        $currentPeriodStart = now()->startOfMonth();
        $currentPeriodEnd = now()->endOfMonth();
        $previousPeriodStart = now()->subMonthNoOverflow()->startOfMonth();
        $previousPeriodEnd = now()->subMonthNoOverflow()->endOfMonth();

        $groupedReadings = MeterReading::query()
            ->select(['id', 'meter_id', 'reading_value', 'reading_date'])
            ->forOrganization($organizationId)
            ->forProperty($propertyId)
            ->whereIn('meter_id', $meters->pluck('id')->all())
            ->comparable()
            ->beforeOrOnDate($currentPeriodEnd)
            ->with('meter:id,type,unit')
            ->latestFirst()
            ->get()
            ->groupBy('meter_id');

        $totals = [];

        foreach ($meters as $meter) {
            $readings = $groupedReadings->get($meter->id) ?? Collection::make();
            $currentMonthTotal = $readings
                ->filter(fn (MeterReading $reading): bool => $reading->reading_date->betweenIncluded($currentPeriodStart, $currentPeriodEnd))
                ->sum(fn (MeterReading $reading): float => (float) $reading->reading_value);
            $previousMonthTotal = $readings
                ->filter(fn (MeterReading $reading): bool => $reading->reading_date->betweenIncluded($previousPeriodStart, $previousPeriodEnd))
                ->sum(fn (MeterReading $reading): float => (float) $reading->reading_value);
            $consumption = max(0, round($currentMonthTotal - $previousMonthTotal, 3));

            if ($consumption === 0.0 && $currentMonthTotal === 0.0 && $previousMonthTotal === 0.0) {
                continue;
            }
            $typeKey = $meter->type?->value ?? 'unknown';

            if (! array_key_exists($typeKey, $totals)) {
                $totals[$typeKey] = [
                    'type' => $meter->type?->label() ?? __('dashboard.not_available'),
                    'unit' => $meter->unit ?? '',
                    'amount' => 0.0,
                ];
            }

            $totals[$typeKey]['amount'] += $consumption;
        }

        return collect($totals)
            ->map(fn (array $row): array => [
                'type' => $row['type'],
                'unit' => $row['unit'],
                'value' => $this->formatDecimal((float) $row['amount'], 3),
                'display' => trim($this->formatDecimal((float) $row['amount'], 3).' '.$row['unit']),
            ])
            ->values()
            ->all();
    }

    private function formatCurrency(float $amount, string $currency): string
    {
        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::CURRENCY);

        return (string) $formatter->formatCurrency($amount, $currency);
    }

    private function formatDecimal(float $value, int $precision): string
    {
        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $precision);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $precision);

        return (string) $formatter->format($value);
    }
}
