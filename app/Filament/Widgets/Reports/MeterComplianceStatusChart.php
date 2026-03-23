<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Reports;

use App\Filament\Support\Admin\Reports\MeterComplianceReportBuilder;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

final class MeterComplianceStatusChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    public ?int $organizationId = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?string $buildingId = null;

    public ?string $propertyId = null;

    public ?string $tenantId = null;

    public ?string $meterType = null;

    public function getHeading(): ?string
    {
        return __('admin.reports.tabs.meter_compliance');
    }

    protected function getData(): array
    {
        if ($this->organizationId === null) {
            return [
                'labels' => [],
                'datasets' => [
                    [
                        'label' => __('admin.reports.tabs.meter_compliance'),
                        'data' => [],
                    ],
                ],
            ];
        }

        $fromDate = filled($this->dateFrom)
            ? Carbon::parse((string) $this->dateFrom)->startOfDay()
            : now()->startOfMonth();
        $toDate = filled($this->dateTo)
            ? Carbon::parse((string) $this->dateTo)->endOfDay()
            : now()->endOfMonth();

        $report = app(MeterComplianceReportBuilder::class)->build(
            $this->organizationId,
            $fromDate,
            $toDate,
            [
                'building_id' => filled($this->buildingId) ? (int) $this->buildingId : null,
                'property_id' => filled($this->propertyId) ? (int) $this->propertyId : null,
                'tenant_id' => filled($this->tenantId) ? (int) $this->tenantId : null,
                'meter_type' => filled($this->meterType) ? (string) $this->meterType : null,
                'status_filter' => 'all',
            ],
        );

        $rows = collect($report['rows'] ?? []);
        $labels = [
            __('admin.reports.states.compliant'),
            __('admin.reports.states.needs_attention'),
            __('admin.reports.states.missing'),
        ];

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('admin.reports.tabs.meter_compliance'),
                    'data' => [
                        $rows->where('compliance_state', 'compliant')->count(),
                        $rows->where('compliance_state', 'needs_attention')->count(),
                        $rows->where('compliance_state', 'missing')->count(),
                    ],
                    'backgroundColor' => [
                        '#10b981',
                        '#f59e0b',
                        '#ef4444',
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
