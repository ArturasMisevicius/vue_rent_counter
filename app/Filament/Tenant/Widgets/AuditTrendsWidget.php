<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use App\Services\Audit\UniversalServiceAuditReporter;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

/**
 * Audit Trends Widget
 * 
 * Displays audit activity trends over time with interactive charts
 * showing change patterns, user activity, and system modifications.
 */
final class AuditTrendsWidget extends ChartWidget
{
    protected static ?string $heading = null;
    
    protected static ?int $sort = 2;
    
    protected static ?string $pollingInterval = '60s';

    public function __construct(
        private readonly UniversalServiceAuditReporter $auditReporter,
    ) {
        parent::__construct();
    }

    protected function getHeading(): ?string
    {
        return __('dashboard.audit.trends_title');
    }

    protected function getDescription(): ?string
    {
        return __('dashboard.audit.trends_description');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $cacheKey = 'audit_trends_' . auth()->user()->currentTeam->id;
        
        return Cache::remember($cacheKey, 600, function () {
            $report = $this->auditReporter->generateReport(
                tenantId: auth()->user()->currentTeam->id,
                startDate: now()->subDays(30),
                endDate: now(),
            );
            
            // Generate daily trend data for the last 30 days
            $dailyData = $this->generateDailyTrendData();
            
            return [
                'datasets' => [
                    [
                        'label' => __('dashboard.audit.total_changes'),
                        'data' => $dailyData['total'],
                        'borderColor' => 'rgb(59, 130, 246)',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'tension' => 0.4,
                    ],
                    [
                        'label' => __('dashboard.audit.user_changes'),
                        'data' => $dailyData['user'],
                        'borderColor' => 'rgb(34, 197, 94)',
                        'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                        'tension' => 0.4,
                    ],
                    [
                        'label' => __('dashboard.audit.system_changes'),
                        'data' => $dailyData['system'],
                        'borderColor' => 'rgb(249, 115, 22)',
                        'backgroundColor' => 'rgba(249, 115, 22, 0.1)',
                        'tension' => 0.4,
                    ],
                ],
                'labels' => $dailyData['labels'],
            ];
        });
    }

    /**
     * Generate daily trend data for the chart.
     */
    private function generateDailyTrendData(): array
    {
        $labels = [];
        $totalData = [];
        $userData = [];
        $systemData = [];
        
        // Generate data for the last 30 days
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M j');
            
            // Get daily audit counts (simplified for demo)
            $dailyReport = $this->auditReporter->generateReport(
                tenantId: auth()->user()->currentTeam->id,
                startDate: $date->startOfDay(),
                endDate: $date->endOfDay(),
            );
            
            $totalData[] = $dailyReport->summary->totalChanges;
            $userData[] = $dailyReport->summary->userChanges;
            $systemData[] = $dailyReport->summary->systemChanges;
        }
        
        return [
            'labels' => $labels,
            'total' => $totalData,
            'user' => $userData,
            'system' => $systemData,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => __('dashboard.audit.date'),
                    ],
                ],
                'y' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => __('dashboard.audit.number_of_changes'),
                    ],
                    'beginAtZero' => true,
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }
}