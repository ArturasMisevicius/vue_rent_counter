<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\Reports;

use App\Enums\MeterReadingValidationStatus;
use App\Models\Meter;
use App\Models\SystemConfiguration;
use App\Models\SystemSetting;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class MeterComplianceReportBuilder extends AbstractReportBuilder
{
    /**
     * @param  array{building_id: int|null, property_id: int|null, tenant_id: int|null, meter_type: string|null, status_filter: string|null}  $filters
     * @return array{
     *     title: string,
     *     description: string,
     *     summary: array<int, array{label: string, value: string}>,
     *     columns: array<int, array{key: string, label: string}>,
     *     rows: array<int, array<string, mixed>>,
     *     empty_state: string
     * }
     */
    public function build(int $organizationId, CarbonInterface $startDate, CarbonInterface $endDate, array $filters): array
    {
        $thresholdDays = $this->thresholdDays();

        $meters = Meter::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'name',
                'identifier',
                'type',
                'status',
                'unit',
                'installed_at',
            ])
            ->with([
                'property:id,organization_id,building_id,name,unit_number',
                'property.building:id,organization_id,name',
                'property.currentAssignment:id,organization_id,property_id,tenant_user_id,assigned_at,unassigned_at',
                'property.currentAssignment.tenant:id,organization_id,name,email',
                'latestReading' => fn ($query) => $query
                    ->select([
                        'id',
                        'organization_id',
                        'property_id',
                        'meter_id',
                        'reading_value',
                        'reading_date',
                        'validation_status',
                    ])
                    ->forOrganization($organizationId)
                    ->beforeOrOnDate($endDate)
                    ->latestFirst(),
            ])
            ->forOrganization($organizationId)
            ->when(
                filled($filters['building_id'] ?? null),
                fn ($query) => $query->whereHas(
                    'property',
                    fn ($query) => $query->where('building_id', $filters['building_id']),
                ),
            )
            ->when(
                filled($filters['property_id'] ?? null),
                fn ($query) => $query->forProperty($filters['property_id']),
            )
            ->when(
                filled($filters['meter_type'] ?? null),
                fn ($query) => $query->where('type', $filters['meter_type']),
            )
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        /** @var Collection<int, array<string, mixed>> $rowData */
        $rowData = $meters
            ->map(function (Meter $meter) use ($filters, $thresholdDays): ?array {
                $assignment = $meter->property?->currentAssignment;

                if (
                    filled($filters['tenant_id'] ?? null)
                    && (int) ($assignment?->tenant_user_id ?? 0) !== (int) $filters['tenant_id']
                ) {
                    return null;
                }

                $latestReading = $meter->latestReading;
                $daysSinceLastReading = $latestReading?->reading_date instanceof CarbonInterface
                    ? $latestReading->reading_date->startOfDay()->diffInDays(now()->startOfDay())
                    : null;

                $complianceState = match (true) {
                    $latestReading === null => 'missing',
                    in_array($latestReading->validation_status, [
                        MeterReadingValidationStatus::PENDING,
                        MeterReadingValidationStatus::REJECTED,
                    ], true) => 'needs_attention',
                    $daysSinceLastReading !== null && $daysSinceLastReading > $thresholdDays => 'needs_attention',
                    default => 'compliant',
                };

                return [
                    'meter_id' => $meter->id,
                    'compliance_state' => $complianceState,
                    'meter' => $meter->name,
                    'tenant' => (string) ($assignment?->tenant?->name ?? __('dashboard.not_available')),
                    'property' => $this->propertyLabel($meter->property),
                    'type' => $meter->type->label(),
                    'latest_reading' => $this->formatDate($latestReading?->reading_date),
                    'days_since_last_reading' => $daysSinceLastReading === null
                        ? __('dashboard.not_available')
                        : (string) $daysSinceLastReading,
                    'raw_days_since_last_reading' => $daysSinceLastReading ?? 100000,
                    'compliance' => __('admin.reports.states.'.$complianceState),
                ];
            })
            ->filter()
            ->when(
                filled($filters['status_filter'] ?? null) && $filters['status_filter'] !== 'all',
                fn (Collection $rows): Collection => $rows->where('compliance_state', $filters['status_filter']),
            )
            ->sortByDesc('raw_days_since_last_reading')
            ->values();

        return [
            'title' => __('admin.reports.tabs.meter_compliance'),
            'description' => __('admin.reports.descriptions.meter_compliance_threshold', ['days' => $thresholdDays]),
            'summary' => [
                [
                    'label' => __('admin.reports.summary.compliant_meters'),
                    'value' => (string) $rowData->where('compliance_state', 'compliant')->count(),
                ],
                [
                    'label' => __('admin.reports.summary.needs_attention'),
                    'value' => (string) $rowData->where('compliance_state', 'needs_attention')->count(),
                ],
                [
                    'label' => __('admin.reports.summary.missing_readings'),
                    'value' => (string) $rowData->where('compliance_state', 'missing')->count(),
                ],
            ],
            'columns' => [
                ['key' => 'meter', 'label' => __('admin.reports.columns.meter')],
                ['key' => 'tenant', 'label' => __('admin.reports.columns.tenant')],
                ['key' => 'property', 'label' => __('admin.reports.columns.property')],
                ['key' => 'type', 'label' => __('admin.reports.columns.type')],
                ['key' => 'latest_reading', 'label' => __('admin.reports.columns.latest_reading')],
                ['key' => 'days_since_last_reading', 'label' => __('admin.reports.columns.days_since_last_reading')],
                ['key' => 'compliance', 'label' => __('admin.reports.columns.compliance')],
            ],
            'rows' => $rowData->all(),
            'empty_state' => __('admin.reports.empty.meter_compliance'),
        ];
    }

    private function thresholdDays(): int
    {
        $systemSetting = SystemSetting::query()
            ->select(['id', 'key', 'value'])
            ->where('key', 'reports.meter_compliance.threshold_days')
            ->first();

        if ($systemSetting !== null) {
            $value = is_array($systemSetting->value) ? ($systemSetting->value['value'] ?? null) : null;

            if (is_numeric($value)) {
                return max((int) $value, 1);
            }
        }

        $systemConfiguration = SystemConfiguration::query()
            ->select(['id', 'key', 'value'])
            ->where('key', 'reports.meter_compliance.threshold_days')
            ->first();

        if ($systemConfiguration !== null) {
            $value = is_array($systemConfiguration->value) ? ($systemConfiguration->value['value'] ?? null) : null;

            if (is_numeric($value)) {
                return max((int) $value, 1);
            }
        }

        return 30;
    }
}
