<?php

namespace App\Support\Admin\Reports;

use App\Models\Meter;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class MeterComplianceReportBuilder extends AbstractReportBuilder
{
    /**
     * @param  array{compliance_state: string|null}  $filters
     * @return array{
     *     title: string,
     *     description: string,
     *     summary: array<int, array{label: string, value: string}>,
     *     columns: array<int, array{key: string, label: string}>,
     *     rows: array<int, array<string, string>>,
     *     empty_state: string
     * }
     */
    public function build(int $organizationId, CarbonInterface $startDate, CarbonInterface $endDate, array $filters): array
    {
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
                'property:id,building_id,name,unit_number',
                'property.building:id,name',
                'readings' => fn ($query) => $query
                    ->select([
                        'id',
                        'organization_id',
                        'property_id',
                        'meter_id',
                        'reading_value',
                        'reading_date',
                        'validation_status',
                    ])
                    ->whereBetween('reading_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orderByDesc('reading_date')
                    ->orderByDesc('id'),
            ])
            ->where('organization_id', $organizationId)
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        /** @var Collection<int, array<string, string>> $rowData */
        $rowData = $meters
            ->map(function (Meter $meter): array {
                $latestReading = $meter->readings->first();
                $readingCount = $meter->readings->count();

                $complianceState = match (true) {
                    $readingCount === 0 => 'missing',
                    ($latestReading?->validation_status?->value ?? null) === 'valid' => 'compliant',
                    default => 'needs_attention',
                };

                return [
                    'compliance_state' => $complianceState,
                    'meter' => $meter->name,
                    'property' => $this->propertyLabel($meter->property),
                    'type' => __('admin.meters.types.'.$meter->type->value),
                    'latest_reading' => $this->formatDate($latestReading?->reading_date),
                    'readings' => (string) $readingCount,
                    'compliance' => __('admin.reports.states.'.$complianceState),
                ];
            })
            ->when(
                filled($filters['compliance_state'] ?? null),
                fn (Collection $rows): Collection => $rows->where('compliance_state', $filters['compliance_state']),
            )
            ->values();

        return [
            'title' => __('admin.reports.tabs.meter_compliance'),
            'description' => __('admin.reports.descriptions.meter_compliance'),
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
                ['key' => 'property', 'label' => __('admin.reports.columns.property')],
                ['key' => 'type', 'label' => __('admin.reports.columns.type')],
                ['key' => 'latest_reading', 'label' => __('admin.reports.columns.latest_reading')],
                ['key' => 'readings', 'label' => __('admin.reports.columns.readings')],
                ['key' => 'compliance', 'label' => __('admin.reports.columns.compliance')],
            ],
            'rows' => $rowData
                ->map(fn (array $row): array => [
                    'meter' => $row['meter'],
                    'property' => $row['property'],
                    'type' => $row['type'],
                    'latest_reading' => $row['latest_reading'],
                    'readings' => $row['readings'],
                    'compliance' => $row['compliance'],
                ])
                ->all(),
            'empty_state' => __('admin.reports.empty.meter_compliance'),
        ];
    }
}
