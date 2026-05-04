<?php

namespace App\Filament\Support\Tenant\Portal;

use App\Enums\PropertyType;
use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Filament\Support\Workspace\WorkspaceResolver;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Str;

class TenantPropertyPresenter
{
    public function __construct(
        private readonly WorkspaceResolver $workspaceResolver,
        private readonly TenantMeterNameLocalizer $meterNameLocalizer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function for(User $tenant, string $selectedYear = 'all', string $selectedMonth = 'all'): array
    {
        $workspace = $this->workspaceResolver->resolveFor($tenant);

        if (! $workspace->isTenant() || $workspace->organizationId === null || $workspace->propertyId === null) {
            return [
                'has_assignment' => false,
                'property_name' => null,
                'property_address' => null,
                'property_building_name' => null,
                'assigned_since' => null,
                'tenant_name' => $tenant->name,
                'tenant_email' => $tenant->email,
                'tenant_phone' => $tenant->phone,
                'property_unit_number' => null,
                'property_floor_area' => null,
                'meter_count' => 0,
                'meters' => [],
                'history_entries' => [],
                'history_count' => 0,
                'available_years' => [],
                'available_months' => [],
                'selected_year' => $selectedYear,
                'selected_month' => $selectedMonth,
            ];
        }

        $organizationId = $workspace->organizationId;

        $tenant = User::query()
            ->select(['id', 'organization_id', 'role', 'name', 'email', 'phone'])
            ->with([
                'currentPropertyAssignment' => fn ($query) => $query
                    ->select(['id', 'organization_id', 'property_id', 'tenant_user_id', 'assigned_at', 'unassigned_at'])
                    ->forOrganization($organizationId)
                    ->forProperty($workspace->propertyId)
                    ->current(),
                'currentPropertyAssignment.property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
                'currentPropertyAssignment.property.building:id,organization_id,name,address_line_1,address_line_2,city,postal_code,country_code',
                'currentPropertyAssignment.property.meters' => fn ($query) => $query
                    ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'type', 'status', 'unit'])
                    ->forOrganization($organizationId)
                    ->orderBy('name'),
                'currentPropertyAssignment.property.meters.latestReading' => fn ($query) => $query
                    ->select(['id', 'organization_id', 'meter_id', 'reading_value', 'reading_date', 'validation_status'])
                    ->forOrganization($organizationId),
            ])
            ->findOrFail($workspace->userId);

        $property = $tenant->currentProperty;

        if ($property === null) {
            return [
                'has_assignment' => false,
                'property_name' => null,
                'property_address' => null,
                'property_building_name' => null,
                'assigned_since' => null,
                'tenant_name' => $tenant->name,
                'tenant_email' => $tenant->email,
                'tenant_phone' => $tenant->phone,
                'property_unit_number' => null,
                'property_floor_area' => null,
                'meter_count' => 0,
                'meters' => [],
                'history_entries' => [],
                'history_count' => 0,
                'available_years' => [],
                'available_months' => [],
                'selected_year' => $selectedYear,
                'selected_month' => $selectedMonth,
            ];
        }

        $historyBaseQuery = MeterReading::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'meter_id',
                'submitted_by_user_id',
                'reading_value',
                'reading_date',
                'validation_status',
                'submission_method',
                'created_at',
            ])
            ->forOrganization($organizationId)
            ->forProperty($property->id)
            ->submittedBy($tenant->id)
            ->with([
                'meter:id,organization_id,property_id,name,identifier,type,unit',
            ])
            ->latestFirst();

        $availableYears = $historyBaseQuery
            ->clone()
            ->get()
            ->map(fn (MeterReading $reading): string => $reading->reading_date->format('Y'))
            ->unique()
            ->values();

        $availableMonths = $historyBaseQuery
            ->clone()
            ->when(
                $selectedYear !== 'all',
                fn ($query) => $query->whereYear('reading_date', (int) $selectedYear),
            )
            ->get()
            ->map(fn (MeterReading $reading): string => $reading->reading_date->format('n'))
            ->unique()
            ->sort(fn (string $left, string $right) => (int) $left <=> (int) $right)
            ->values();

        $historyEntries = $historyBaseQuery
            ->clone()
            ->when(
                $selectedYear !== 'all',
                fn ($query) => $query->whereYear('reading_date', (int) $selectedYear),
            )
            ->when(
                $selectedMonth !== 'all',
                fn ($query) => $query->whereMonth('reading_date', (int) $selectedMonth),
            )
            ->get()
            ->map(fn (MeterReading $reading): array => [
                'id' => $reading->id,
                'meter_name' => $this->meterNameLocalizer->displayName($reading->meter),
                'meter_identifier' => $reading->meter?->identifier ?? '—',
                'reading_value' => $this->formatDecimal((float) $reading->reading_value, 3),
                'unit' => $reading->meter?->unit ?? '',
                'reading_date' => LocalizedDateFormatter::date($reading->reading_date),
                'month_label' => $reading->reading_date->translatedFormat('F Y'),
                'status_label' => $reading->validation_status?->label() ?? __('dashboard.not_available'),
                'submitted_via' => $reading->submission_method?->label() ?? __('dashboard.not_available'),
                'submitted_at' => LocalizedDateFormatter::dateTime($reading->created_at),
            ])
            ->all();

        return [
            'has_assignment' => true,
            'tenant_name' => $tenant->name,
            'tenant_email' => $tenant->email,
            'tenant_phone' => $tenant->phone,
            'property_name' => $property->name,
            'property_display_name' => $this->displayPropertyName($property),
            'property_address' => $property->address,
            'property_building_name' => $property->building?->name,
            'property_unit_number' => $property->unit_number,
            'property_floor_area' => $property->areaDisplay(),
            'assigned_since' => LocalizedDateFormatter::date($tenant->currentPropertyAssignment?->assigned_at),
            'meter_count' => $property->meters->count(),
            'meters' => $property->meters->map(fn ($meter) => [
                'id' => $meter->id,
                'name' => $meter->name,
                'display_name' => $this->meterNameLocalizer->displayName($meter),
                'identifier' => $meter->identifier,
                'unit' => $meter->unit,
                'last_reading' => $meter->latestReading
                    ? __('tenant.pages.property.last_reading', [
                        'value' => $this->formatDecimal((float) $meter->latestReading->reading_value, 3),
                        'unit' => $meter->unit,
                        'date' => LocalizedDateFormatter::date($meter->latestReading->reading_date),
                    ])
                    : __('tenant.pages.property.last_reading_none'),
                'has_reading' => $meter->latestReading !== null,
            ])->all(),
            'history_entries' => $historyEntries,
            'history_count' => count($historyEntries),
            'available_years' => $availableYears->all(),
            'available_months' => $availableMonths->all(),
            'selected_year' => $selectedYear,
            'selected_month' => $selectedMonth,
        ];
    }

    private function formatDecimal(float $value, int $precision): string
    {
        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $precision);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $precision);

        return (string) $formatter->format($value);
    }

    private function displayPropertyName(Property $property): string
    {
        $name = trim((string) $property->name);
        $unitNumber = trim((string) $property->unit_number);

        if ($unitNumber === '') {
            return $name !== '' ? $name : __('dashboard.not_available');
        }

        $generatedTypeLabel = $this->generatedPropertyTypeLabel($name, $unitNumber);

        if ($name !== '' && $generatedTypeLabel === null) {
            return $name;
        }

        $typeLabel = $generatedTypeLabel ?? $property->type?->label();

        if ($typeLabel === null) {
            return $name !== '' ? $name : __('dashboard.not_available');
        }

        return __('tenant.pages.property.property_unit_label', [
            'type' => $typeLabel,
            'unit' => $unitNumber,
        ]);
    }

    private function generatedPropertyTypeLabel(string $name, string $unitNumber): ?string
    {
        foreach (PropertyType::cases() as $type) {
            $generatedEnglishName = trim((string) __($type->translationKey(), [], 'en').' '.$unitNumber);

            if (Str::of($name)->lower()->exactly(Str::of($generatedEnglishName)->lower()->value())) {
                return $type->label();
            }
        }

        return null;
    }
}
