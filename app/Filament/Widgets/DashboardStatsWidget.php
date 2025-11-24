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
            Stat::make('Total Properties', Property::where('tenant_id', $tenantId)->count())
                ->description('Properties in your portfolio')
                ->descriptionIcon('heroicon-o-building-office')
                ->color('success'),

            Stat::make('Total Buildings', Building::where('tenant_id', $tenantId)->count())
                ->description('Buildings managed')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('info'),

            Stat::make('Active Tenants', User::where('tenant_id', $tenantId)
                ->where('role', UserRole::TENANT)
                ->where('is_active', true)
                ->count())
                ->description('Active tenant accounts')
                ->descriptionIcon('heroicon-o-users')
                ->color('warning'),

            Stat::make('Draft Invoices', Invoice::where('tenant_id', $tenantId)
                ->whereNull('finalized_at')
                ->count())
                ->description('Invoices pending finalization')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('danger'),

            Stat::make('Pending Readings', MeterReading::whereHas('meter', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
                ->whereDoesntHave('auditTrail')
                ->count())
                ->description('Meter readings to verify')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('warning'),

            Stat::make('Total Revenue (This Month)', $this->formatRevenue(
                Invoice::where('tenant_id', $tenantId)
                    ->whereNotNull('finalized_at')
                    ->whereMonth('created_at', now()->month)
                    ->sum('total_amount')
            ))
                ->description('Revenue from finalized invoices')
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
            Stat::make('Total Properties', Property::where('tenant_id', $tenantId)->count())
                ->description('Properties you manage')
                ->descriptionIcon('heroicon-o-building-office')
                ->color('success'),

            Stat::make('Total Buildings', Building::where('tenant_id', $tenantId)->count())
                ->description('Buildings under management')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('info'),

            Stat::make('Pending Readings', MeterReading::whereHas('meter', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
                ->whereDoesntHave('auditTrail')
                ->count())
                ->description('Meter readings to verify')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('warning'),

            Stat::make('Draft Invoices', Invoice::where('tenant_id', $tenantId)
                ->whereNull('finalized_at')
                ->count())
                ->description('Invoices pending finalization')
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
            Stat::make('Your Property', $property?->address ?? 'N/A')
                ->description('Your assigned property')
                ->descriptionIcon('heroicon-o-building-office')
                ->color('info'),

            Stat::make('Your Invoices', Invoice::whereHas('property', function ($query) use ($user) {
                $query->where('properties.id', $user->property_id);
            })->count())
                ->description('Total invoices')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('success'),

            Stat::make('Unpaid Invoices', Invoice::whereHas('property', function ($query) use ($user) {
                $query->where('properties.id', $user->property_id);
            })
                ->where('status', InvoiceStatus::FINALIZED)
                ->count())
                ->description('Invoices awaiting payment')
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
