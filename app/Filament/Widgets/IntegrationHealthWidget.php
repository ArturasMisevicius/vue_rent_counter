<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\Integration\IntegrationResilienceHandler;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class IntegrationHealthWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static bool $isLazy = false;
    
    protected int | string | array $columnSpan = 'full';

    public function __construct(
        private readonly IntegrationResilienceHandler $resilienceHandler
    ) {
        parent::__construct();
    }

    protected function getStats(): array
    {
        $healthStatus = $this->resilienceHandler->getHealthStatus();
        
        return [
            Stat::make('Overall Health', number_format($healthStatus['overall_health'], 1) . '%')
                ->description('Integration services health')
                ->descriptionIcon('heroicon-m-heart')
                ->color($this->getHealthColor($healthStatus['overall_health']))
                ->chart($this->getHealthChart()),
                
            Stat::make('Healthy Services', $healthStatus['healthy_services'])
                ->description("out of {$healthStatus['total_services']} total")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($healthStatus['healthy_services'] === $healthStatus['total_services'] ? 'success' : 'warning'),
                
            Stat::make('Offline Mode', $healthStatus['offline_mode'] ? 'Enabled' : 'Disabled')
                ->description('Fallback data availability')
                ->descriptionIcon($healthStatus['offline_mode'] ? 'heroicon-m-wifi' : 'heroicon-m-signal')
                ->color($healthStatus['offline_mode'] ? 'warning' : 'success'),
        ];
    }
    
    private function getHealthColor(float $health): string
    {
        if ($health >= 90) {
            return 'success';
        } elseif ($health >= 70) {
            return 'warning';
        } else {
            return 'danger';
        }
    }
    
    private function getHealthChart(): array
    {
        // Generate a simple chart showing health over time
        // In a real implementation, you'd store historical data
        return [85, 88, 92, 89, 95, 91, 94];
    }
}