<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Pages;

use App\Filament\Clusters\SuperAdmin;
use App\Filament\Clusters\SuperAdmin\Widgets\TenantOverviewWidget;
use App\Filament\Clusters\SuperAdmin\Widgets\SystemMetricsWidget;
use App\Filament\Clusters\SuperAdmin\Widgets\RecentActivityWidget;
use App\Filament\Tenant\Widgets\AuditOverviewWidget;
use App\Filament\Tenant\Widgets\AuditTrendsWidget;
use App\Filament\Tenant\Widgets\ComplianceStatusWidget;
use App\Filament\Tenant\Widgets\AnomalyDetectionWidget;
use Filament\Pages\Dashboard as BaseDashboard;

final class Dashboard extends BaseDashboard
{
    protected static ?string $cluster = SuperAdmin::class;
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?int $navigationSort = 1;

    public function getTitle(): string
    {
        return __('superadmin.dashboard.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('superadmin.dashboard.title');
    }

    public function getWidgets(): array
    {
        return [
            TenantOverviewWidget::class,
            SystemMetricsWidget::class,
            RecentActivityWidget::class,
            // System-wide audit widgets for SuperAdmin
            AuditOverviewWidget::class,
            AuditTrendsWidget::class,
            ComplianceStatusWidget::class,
            AnomalyDetectionWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
            'xl' => 4,
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->is_super_admin ?? false;
    }
}