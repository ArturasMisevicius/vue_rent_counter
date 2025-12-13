<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Services\DashboardCacheService;
use Illuminate\Support\Facades\DB;

/**
 * Platform Usage Chart Widget with optimized Chart.js rendering
 * 
 * Shows platform growth trends over time with performance optimizations:
 * - Lazy loading
 * - Cached data
 * - Optimized Chart.js configuration
 * - Reduced data points for better performance
 */
class PlatformUsageChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Platform Usage Trends';
    protected static ?int $sort = 4;
    
    // Enable lazy loading for better performance
    protected static bool $isLazy = true;
    
    // Polling interval (in seconds) - refresh every 5 minutes
    protected static ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        $cacheService = app(DashboardCacheService::class);
        $usageData = $cacheService->getPlatformUsageStats();
        
        // Get growth data for the last 12 months (optimized with fewer data points)
        $monthlyData = $this->getMonthlyGrowthData();
        
        return [
            'datasets' => [
                [
                    'label' => 'Organizations',
                    'data' => $monthlyData['organizations'],
                    'borderColor' => 'rgb(59, 130, 246)', // Blue
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4, // Smooth curves
                ],
                [
                    'label' => 'Properties',
                    'data' => $monthlyData['properties'],
                    'borderColor' => 'rgb(16, 185, 129)', // Green
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Users',
                    'data' => $monthlyData['users'],
                    'borderColor' => 'rgb(245, 158, 11)', // Yellow
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $monthlyData['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * Get optimized Chart.js options for better performance
     */
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'plugins' => [
                'legend' => [
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
                        'text' => 'Month',
                    ],
                ],
                'y' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Count',
                    ],
                    'beginAtZero' => true,
                ],
            ],
            // Performance optimizations
            'animation' => [
                'duration' => 750, // Faster animations
            ],
            'elements' => [
                'point' => [
                    'radius' => 3,
                    'hoverRadius' => 5,
                ],
                'line' => [
                    'borderWidth' => 2,
                ],
            ],
            // Optimize for large datasets
            'parsing' => false,
            'normalized' => true,
        ];
    }

    /**
     * Get monthly growth data with optimized query
     */
    private function getMonthlyGrowthData(): array
    {
        // Get data for last 12 months with single optimized query
        $months = [];
        $labels = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('Y-m');
            $labels[] = $date->format('M Y');
        }
        
        // Single query to get all counts by month
        $data = DB::select("
            SELECT 
                strftime('%Y-%m', created_at) as month,
                'organizations' as type,
                COUNT(*) as count
            FROM organizations 
            WHERE strftime('%Y-%m', created_at) IN ('" . implode("','", $months) . "')
            GROUP BY strftime('%Y-%m', created_at)
            
            UNION ALL
            
            SELECT 
                strftime('%Y-%m', created_at) as month,
                'properties' as type,
                COUNT(*) as count
            FROM properties 
            WHERE strftime('%Y-%m', created_at) IN ('" . implode("','", $months) . "')
            GROUP BY strftime('%Y-%m', created_at)
            
            UNION ALL
            
            SELECT 
                strftime('%Y-%m', created_at) as month,
                'users' as type,
                COUNT(*) as count
            FROM users 
            WHERE tenant_id IS NOT NULL 
            AND strftime('%Y-%m', created_at) IN ('" . implode("','", $months) . "')
            GROUP BY strftime('%Y-%m', created_at)
        ");
        
        // Process results into arrays
        $organizationsData = array_fill(0, 12, 0);
        $propertiesData = array_fill(0, 12, 0);
        $usersData = array_fill(0, 12, 0);
        
        foreach ($data as $row) {
            $monthIndex = array_search($row->month, $months);
            if ($monthIndex !== false) {
                switch ($row->type) {
                    case 'organizations':
                        $organizationsData[$monthIndex] = (int) $row->count;
                        break;
                    case 'properties':
                        $propertiesData[$monthIndex] = (int) $row->count;
                        break;
                    case 'users':
                        $usersData[$monthIndex] = (int) $row->count;
                        break;
                }
            }
        }
        
        return [
            'organizations' => $organizationsData,
            'properties' => $propertiesData,
            'users' => $usersData,
            'labels' => $labels,
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}