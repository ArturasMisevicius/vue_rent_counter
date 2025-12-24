<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Pages;

use App\Enums\UserRole;
use App\Filament\Tenant\Widgets\AnomalyDetectionWidget;
use App\Filament\Tenant\Widgets\AuditChangeHistoryWidget;
use App\Filament\Tenant\Widgets\AuditOverviewWidget;
use App\Filament\Tenant\Widgets\AuditTrendsWidget;
use App\Filament\Tenant\Widgets\ComplianceStatusWidget;
use App\Filament\Tenant\Widgets\ConfigurationRollbackWidget;
use App\Filament\Tenant\Widgets\ConsumptionOverviewWidget;
use App\Filament\Tenant\Widgets\CostTrackingWidget;
use App\Filament\Tenant\Widgets\MultiUtilityComparisonWidget;
use App\Filament\Tenant\Widgets\RealTimeCostWidget;
use App\Filament\Tenant\Widgets\ServiceDrillDownWidget;
use App\Filament\Tenant\Widgets\UtilityAnalyticsWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

final class Dashboard extends BaseDashboard
{
    protected static string $view = 'filament.tenant.pages.dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Dashboard';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        
        return $user && in_array($user->role, [UserRole::TENANT, UserRole::MANAGER], true);
    }

    public function getWidgets(): array
    {
        $user = Auth::user();
        
        // Base utility widgets for all tenant dashboard users
        $widgets = [
            ConsumptionOverviewWidget::class,
            CostTrackingWidget::class,
            MultiUtilityComparisonWidget::class,
            RealTimeCostWidget::class,
            ServiceDrillDownWidget::class,
            UtilityAnalyticsWidget::class,
        ];
        
        // Add audit widgets for managers and admins
        if ($user && in_array($user->role, [UserRole::MANAGER, UserRole::ADMIN], true)) {
            $widgets = array_merge($widgets, [
                AuditOverviewWidget::class,
                AuditTrendsWidget::class,
                ComplianceStatusWidget::class,
                AnomalyDetectionWidget::class,
                AuditChangeHistoryWidget::class,
                ConfigurationRollbackWidget::class,
            ]);
        }
        
        return $widgets;
    }

    public function getColumns(): array|int
    {
        return [
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
        ];
    }

    /**
     * Get quick stats for the dashboard header
     */
    public function getQuickStats(): array
    {
        $user = Auth::user();
        
        if (!$user || !$user->currentTeam) {
            return [];
        }

        $properties = $user->currentTeam->properties()->with([
            'meters.serviceConfiguration.utilityService',
            'meters.readings' => fn($q) => $q->latest()->limit(1)
        ])->get();

        $totalProperties = $properties->count();
        $activeServices = $properties->flatMap(fn($p) => $p->meters)
            ->pluck('serviceConfiguration.utilityService.name')
            ->unique()
            ->count();

        $currentMonthReadings = $properties->flatMap(fn($p) => $p->meters)
            ->flatMap(fn($m) => $m->readings)
            ->filter(fn($r) => $r->created_at->isCurrentMonth())
            ->count();

        $pendingReadings = $properties->flatMap(fn($p) => $p->meters)
            ->filter(fn($m) => !$m->readings()->whereMonth('created_at', now()->month)->exists())
            ->count();

        return [
            'total_properties' => $totalProperties,
            'active_services' => $activeServices,
            'current_month_readings' => $currentMonthReadings,
            'pending_readings' => $pendingReadings,
        ];
    }

    /**
     * Get recent activity for the dashboard
     */
    public function getRecentActivity(): array
    {
        $user = Auth::user();
        
        if (!$user || !$user->currentTeam) {
            return [];
        }

        $recentReadings = $user->currentTeam->properties()
            ->with([
                'meters.readings' => fn($q) => $q->with(['meter.serviceConfiguration.utilityService'])
                    ->latest()
                    ->limit(5)
            ])
            ->get()
            ->flatMap(fn($p) => $p->meters)
            ->flatMap(fn($m) => $m->readings)
            ->sortByDesc('created_at')
            ->take(10);

        return $recentReadings->map(function ($reading) {
            return [
                'type' => 'meter_reading',
                'title' => "New {$reading->meter->serviceConfiguration->utilityService->name} reading",
                'description' => "Property: {$reading->meter->property->name}",
                'value' => $reading->value,
                'unit' => $reading->meter->serviceConfiguration->utilityService->unit_of_measurement,
                'created_at' => $reading->created_at,
            ];
        })->toArray();
    }

    /**
     * Get utility service breakdown
     */
    public function getUtilityBreakdown(): array
    {
        $user = Auth::user();
        
        if (!$user || !$user->currentTeam) {
            return [];
        }

        $services = $user->currentTeam->properties()
            ->with([
                'meters.serviceConfiguration.utilityService',
                'meters.readings' => fn($q) => $q->whereMonth('created_at', now()->month)
            ])
            ->get()
            ->flatMap(fn($p) => $p->meters)
            ->groupBy('serviceConfiguration.utilityService.name')
            ->map(function ($meters, $serviceName) {
                $totalConsumption = $meters->flatMap(fn($m) => $m->readings)->sum('value');
                $meterCount = $meters->count();
                
                return [
                    'name' => $serviceName,
                    'meter_count' => $meterCount,
                    'total_consumption' => $totalConsumption,
                    'unit' => $meters->first()->serviceConfiguration->utilityService->unit_of_measurement ?? '',
                ];
            });

        return $services->toArray();
    }
}