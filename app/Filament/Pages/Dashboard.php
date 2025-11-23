<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    protected static ?string $title = 'Dashboard';

    public function getWidgets(): array
    {
        return [
            DashboardStatsWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }
}

class DashboardStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        
        if (!$user) {
            return [];
        }

        // Admin sees their tenant-scoped data
        if ($user->role === UserRole::ADMIN) {
            return [
                Stat::make('Total Properties', Property::where('tenant_id', $user->tenant_id)->count())
                    ->description('Properties in your portfolio')
                    ->descriptionIcon('heroicon-o-building-office')
                    ->color('success'),

                Stat::make('Total Buildings', Building::where('tenant_id', $user->tenant_id)->count())
                    ->description('Buildings managed')
                    ->descriptionIcon('heroicon-o-building-office-2')
                    ->color('info'),

                Stat::make('Active Tenants', User::where('tenant_id', $user->tenant_id)
                    ->where('role', UserRole::TENANT)
                    ->where('is_active', true)
                    ->count())
                    ->description('Active tenant accounts')
                    ->descriptionIcon('heroicon-o-users')
                    ->color('warning'),

                Stat::make('Draft Invoices', Invoice::where('tenant_id', $user->tenant_id)
                    ->whereNull('finalized_at')
                    ->count())
                    ->description('Invoices pending finalization')
                    ->descriptionIcon('heroicon-o-document-text')
                    ->color('danger'),

                Stat::make('Pending Readings', MeterReading::whereHas('meter', function ($query) use ($user) {
                    $query->where('tenant_id', $user->tenant_id);
                })
                    ->whereNull('verified_at')
                    ->count())
                    ->description('Meter readings to verify')
                    ->descriptionIcon('heroicon-o-chart-bar')
                    ->color('warning'),

                Stat::make('Total Revenue (This Month)', function () use ($user) {
                    $total = Invoice::where('tenant_id', $user->tenant_id)
                        ->whereNotNull('finalized_at')
                        ->whereMonth('created_at', now()->month)
                        ->sum('total_amount');
                    return '€' . number_format($total / 100, 2);
                })
                    ->description('Revenue from finalized invoices')
                    ->descriptionIcon('heroicon-o-currency-euro')
                    ->color('success'),
            ];
        }

        // Manager sees similar stats but might have different permissions
        if ($user->role === UserRole::MANAGER) {
            return [
                Stat::make('Total Properties', Property::where('tenant_id', $user->tenant_id)->count())
                    ->description('Properties you manage')
                    ->descriptionIcon('heroicon-o-building-office')
                    ->color('success'),

                Stat::make('Total Buildings', Building::where('tenant_id', $user->tenant_id)->count())
                    ->description('Buildings under management')
                    ->descriptionIcon('heroicon-o-building-office-2')
                    ->color('info'),

                Stat::make('Pending Readings', MeterReading::whereHas('meter', function ($query) use ($user) {
                    $query->where('tenant_id', $user->tenant_id);
                })
                    ->whereNull('verified_at')
                    ->count())
                    ->description('Meter readings to verify')
                    ->descriptionIcon('heroicon-o-chart-bar')
                    ->color('warning'),

                Stat::make('Draft Invoices', Invoice::where('tenant_id', $user->tenant_id)
                    ->whereNull('finalized_at')
                    ->count())
                    ->description('Invoices pending finalization')
                    ->descriptionIcon('heroicon-o-document-text')
                    ->color('danger'),
            ];
        }

        // Tenant sees their own property data
        if ($user->role === UserRole::TENANT && $user->property_id) {
            return [
                Stat::make('Your Property', Property::find($user->property_id)?->address ?? 'N/A')
                    ->description('Your assigned property')
                    ->descriptionIcon('heroicon-o-building-office')
                    ->color('info'),

                Stat::make('Your Invoices', Invoice::whereHas('property', function ($query) use ($user) {
                    $query->where('id', $user->property_id);
                })->count())
                    ->description('Total invoices')
                    ->descriptionIcon('heroicon-o-document-text')
                    ->color('success'),

                Stat::make('Unpaid Invoices', Invoice::whereHas('property', function ($query) use ($user) {
                    $query->where('id', $user->property_id);
                })
                    ->whereNotNull('finalized_at')
                    ->whereNull('paid_at')
                    ->count())
                    ->description('Invoices awaiting payment')
                    ->descriptionIcon('heroicon-o-exclamation-circle')
                    ->color('danger'),
            ];
        }

        return [];
    }
}
