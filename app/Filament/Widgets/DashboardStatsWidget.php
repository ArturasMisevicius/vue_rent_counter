<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class DashboardStatsWidget extends StatsOverviewWidget
{
    /**
     * Cache duration in seconds for stats
     */
    protected int $cacheDuration = 300; // 5 minutes

    protected function getStats(): array
    {
        $user = auth()->user();
        
        if (!$user) {
            return [];
        }

        // Cache key based on user ID and role to ensure proper isolation
        $cacheKey = sprintf('dashboard_stats_%s_%s', $user->id, $user->role->value);

        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($user) {
            return match ($user->role) {
                UserRole::ADMIN => $this->getAdminStats($user),
                UserRole::MANAGER => $this->getManagerStats($user),
                UserRole::TENANT => $this->getTenantStats($user),
                default => [],
            };
        });
    }

    /**
     * Get statistics for admin users
     */
    protected function getAdminStats(User $user): array
    {
        $tenantId = $user->tenant_id;

        return [
            Stat::make(__('dashboard.widgets.admin.total_properties.label'), Property::where('tenant_id', $tenantId)->count())
                ->description(__('dashboard.widgets.admin.total_properties.description'))
                ->descriptionIcon('heroicon-o-building-office')
                ->color('success'),

            Stat::make(__('dashboard.widgets.admin.total_buildings.label'), Building::where('tenant_id', $tenantId)->count())
                ->description(__('dashboard.widgets.admin.total_buildings.description'))
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('info'),

            Stat::make(__('dashboard.widgets.admin.active_tenants.label'), User::where('tenant_id', $tenantId)
                ->where('role', UserRole::TENANT)
                ->where('is_active', true)
                ->count())
                ->description(__('dashboard.widgets.admin.active_tenants.description'))
                ->descriptionIcon('heroicon-o-users')
                ->color('warning'),

            Stat::make(__('dashboard.widgets.admin.draft_invoices.label'), Invoice::where('tenant_id', $tenantId)
                ->whereNull('finalized_at')
                ->count())
                ->description(__('dashboard.widgets.admin.draft_invoices.description'))
                ->descriptionIcon('heroicon-o-document-text')
                ->color('danger'),

            Stat::make(__('dashboard.widgets.admin.pending_readings.label'), MeterReading::whereHas('meter', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
                ->whereDoesntHave('auditTrail')
                ->count())
                ->description(__('dashboard.widgets.admin.pending_readings.description'))
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('warning'),

            Stat::make(__('dashboard.widgets.admin.total_revenue.label'), $this->formatRevenue(
                Invoice::where('tenant_id', $tenantId)
                    ->whereNotNull('finalized_at')
                    ->whereMonth('created_at', now()->month)
                    ->sum('total_amount')
            ))
                ->description(__('dashboard.widgets.admin.total_revenue.description'))
                ->descriptionIcon('heroicon-o-currency-euro')
                ->color('success'),
        ];
    }

    /**
     * Get statistics for manager users
     */
    protected function getManagerStats(User $user): array
    {
        $tenantId = $user->tenant_id;

        return [
            Stat::make(__('dashboard.widgets.manager.total_properties.label'), Property::where('tenant_id', $tenantId)->count())
                ->description(__('dashboard.widgets.manager.total_properties.description'))
                ->descriptionIcon('heroicon-o-building-office')
                ->color('success'),

            Stat::make(__('dashboard.widgets.manager.total_buildings.label'), Building::where('tenant_id', $tenantId)->count())
                ->description(__('dashboard.widgets.manager.total_buildings.description'))
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('info'),

            Stat::make(__('dashboard.widgets.manager.pending_readings.label'), MeterReading::whereHas('meter', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
                ->whereDoesntHave('auditTrail')
                ->count())
                ->description(__('dashboard.widgets.manager.pending_readings.description'))
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('warning'),

            Stat::make(__('dashboard.widgets.manager.draft_invoices.label'), Invoice::where('tenant_id', $tenantId)
                ->whereNull('finalized_at')
                ->count())
                ->description(__('dashboard.widgets.manager.draft_invoices.description'))
                ->descriptionIcon('heroicon-o-document-text')
                ->color('danger'),
        ];
    }

    /**
     * Get statistics for tenant users
     */
    protected function getTenantStats(User $user): array
    {
        if (!$user->property_id) {
            return [];
        }

        $property = Property::find($user->property_id);

        return [
            Stat::make(__('dashboard.widgets.tenant.property.label'), $property?->address ?? __('app.common.na'))
                ->description(__('dashboard.widgets.tenant.property.description'))
                ->descriptionIcon('heroicon-o-building-office')
                ->color('info'),

            Stat::make(__('dashboard.widgets.tenant.invoices.label'), Invoice::whereHas('property', function ($query) use ($user) {
                $query->where('properties.id', $user->property_id);
            })->count())
                ->description(__('dashboard.widgets.tenant.invoices.description'))
                ->descriptionIcon('heroicon-o-document-text')
                ->color('success'),

            Stat::make(__('dashboard.widgets.tenant.unpaid.label'), Invoice::whereHas('property', function ($query) use ($user) {
                $query->where('properties.id', $user->property_id);
            })
                ->where('status', InvoiceStatus::FINALIZED)
                ->count())
                ->description(__('dashboard.widgets.tenant.unpaid.description'))
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color('danger'),
        ];
    }

    /**
     * Format revenue amount in euros
     */
    protected function formatRevenue(int $amountInCents): string
    {
        return '€' . number_format($amountInCents / 100, 2);
    }
}
