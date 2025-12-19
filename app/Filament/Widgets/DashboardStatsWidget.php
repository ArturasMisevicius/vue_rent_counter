<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class DashboardStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();
        
        if (!$user) {
            return [];
        }

        $cacheKey = "dashboard_stats_{$user->id}_{$user->tenant_id}";
        
        return Cache::remember($cacheKey, 300, function () use ($user) {
            return match ($user->role) {
                UserRole::SUPERADMIN => $this->getSuperadminStats(),
                UserRole::ADMIN => $this->getAdminStats($user),
                UserRole::MANAGER => $this->getManagerStats($user),
                UserRole::TENANT => $this->getTenantStats($user),
                default => [],
            };
        });
    }

    private function getSuperadminStats(): array
    {
        return [
            Stat::make('Total Properties', Property::count())
                ->description('Across all organizations')
                ->color('success')
                ->url(route('filament.admin.resources.properties.index')),
            Stat::make('Total Buildings', Building::count())
                ->description('Across all organizations')
                ->color('info')
                ->url(route('filament.admin.resources.buildings.index')),
            Stat::make('Total Users', User::whereNotNull('tenant_id')->count())
                ->description('Active tenant users')
                ->color('warning')
                ->url(route('filament.admin.resources.users.index')),
            Stat::make('Total Invoices', Invoice::count())
                ->description('All invoices generated')
                ->color('primary')
                ->url(route('filament.admin.resources.invoices.index')),
        ];
    }

    private function getAdminStats(User $user): array
    {
        return [
            Stat::make('Total Properties', Property::where('tenant_id', $user->tenant_id)->count())
                ->description('In your organization')
                ->color('success')
                ->url(route('filament.admin.resources.properties.index')),
            Stat::make('Total Buildings', Building::where('tenant_id', $user->tenant_id)->count())
                ->description('In your organization')
                ->color('info')
                ->url(route('filament.admin.resources.buildings.index')),
            Stat::make('Total Users', User::where('tenant_id', $user->tenant_id)->count())
                ->description('In your organization')
                ->color('warning')
                ->url(route('filament.admin.resources.users.index')),
            Stat::make('Total Invoices', Invoice::where('tenant_id', $user->tenant_id)->count())
                ->description('Generated this month')
                ->color('primary')
                ->url(route('filament.admin.resources.invoices.index')),
        ];
    }

    private function getManagerStats(User $user): array
    {
        return [
            Stat::make('Total Properties', Property::where('tenant_id', $user->tenant_id)->count())
                ->description('In your organization')
                ->color('success')
                ->url(route('filament.admin.resources.properties.index')),
            Stat::make('Total Buildings', Building::where('tenant_id', $user->tenant_id)->count())
                ->description('In your organization')
                ->color('info')
                ->url(route('filament.admin.resources.buildings.index')),
            Stat::make('Recent Invoices', Invoice::where('tenant_id', $user->tenant_id)
                ->where('created_at', '>=', now()->subDays(30))
                ->count())
                ->description('Last 30 days')
                ->color('primary')
                ->url(route('filament.admin.resources.invoices.index')),
        ];
    }

    private function getTenantStats(User $user): array
    {
        $property = Property::where('tenant_id', $user->tenant_id)->first();

        if (!$property) {
            return [
                Stat::make('No Property', '0')
                    ->description('No property assigned')
                    ->color('danger'),
            ];
        }

        return [
            Stat::make('Your Property', $property->name)
                ->description('Assigned property')
                ->color('success')
                ->url($property ? route('filament.admin.resources.properties.view', ['record' => $property->id]) : null),
            Stat::make('Recent Invoices', Invoice::where('tenant_id', $user->tenant_id)
                ->where('created_at', '>=', now()->subDays(30))
                ->count())
                ->description('Last 30 days')
                ->color('primary')
                ->url(route('filament.admin.resources.invoices.index')),
        ];
    }
}