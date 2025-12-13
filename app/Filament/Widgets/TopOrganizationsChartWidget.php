<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Services\QueryOptimizationService;
use Illuminate\Support\Facades\Cache;

/**
 * Top Organizations Chart Widget with optimized rendering
 * 
 * Shows top 10 organizations by property count with performance optimizations
 */
class TopOrganizationsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Top Organizations by Properties';
    protected static ?int $sort = 5;
    
    // Enable lazy loading for better performance
    protected static bool $isLazy = true;
    
    // Polling interval - refresh every 10 minutes
    protected static ?string $pollingInterval = '600s';

    protected function getData(): array
    {
        $queryService = app(QueryOptimizationService::class);
        $topOrgs = $queryService->getTopOrganizations('properties', 10);
        
        $labels = [];
        $data = [];
        $colors = [];
        
        // Generate colors for each organization
        $colorPalette = [
            'rgb(59, 130, 246)',   // Blue
            'rgb(16, 185, 129)',   // Green
            'rgb(245, 158, 11)',   // Yellow
            'rgb(239, 68, 68)',    // Red
            'rgb(139, 92, 246)',   // Purple
            'rgb(236, 72, 153)',   // Pink
            'rgb(14, 165, 233)',   // Sky
            'rgb(34, 197, 94)',    // Emerald
            'rgb(251, 146, 60)',   // Orange
            'rgb(168, 85, 247)',   // Violet
        ];
        
        foreach ($topOrgs as $index => $org) {
            $labels[] = $org['name'];
            $data[] = $org['properties_count'] ?? 0;
            $colors[] = $colorPalette[$index % count($colorPalette)];
        }
        
        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Get optimized Chart.js options
     */
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'indexAxis' => 'y', // Horizontal bar chart
            'plugins' => [
                'legend' => [
                    'display' => false, // Hide legend for cleaner look
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => 'function(context) { return context.parsed.x + " properties"; }',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Number of Properties',
                    ],
                    'beginAtZero' => true,
                ],
                'y' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Organizations',
                    ],
                ],
            ],
            // Performance optimizations
            'animation' => [
                'duration' => 500, // Faster animations
            ],
            'elements' => [
                'bar' => [
                    'borderRadius' => 4,
                ],
            ],
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}