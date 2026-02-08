<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\MeterReading;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * Property Stats Widget for Tenant Dashboard
 * 
 * Shows basic statistics about the tenant's property:
 * - Total meters
 * - Recent readings
 * - Unpaid invoices
 * - Current month consumption
 */
final class PropertyStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        $user = Auth::user();
        
        return $user && $user->role === UserRole::TENANT && $user->property_id;
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        
        if (!$user || !$user->property_id) {
            return [];
        }

        // Get property with relationships
        $property = $user->property()->with([
            'meters',
            'meters.readings' => function ($query) {
                $query->latest()->limit(1);
            }
        ])->first();

        if (!$property) {
            return [];
        }

        // Calculate stats
        $totalMeters = $property->meters->count();
        
        $recentReadings = $property->meters
            ->filter(fn($meter) => $meter->readings->isNotEmpty())
            ->count();
        
        $unpaidInvoices = Invoice::where('property_id', $property->id)
            ->whereIn('status', [InvoiceStatus::FINALIZED, InvoiceStatus::OVERDUE])
            ->count();
        
        $currentMonthReadings = MeterReading::whereHas('meter', function ($query) use ($property) {
            $query->where('property_id', $property->id);
        })
        ->whereMonth('reading_date', now()->month)
        ->whereYear('reading_date', now()->year)
        ->count();

        return [
            Stat::make(__('app.stats.total_meters'), $totalMeters)
                ->description(__('app.stats.installed_meters'))
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),
            
            Stat::make(__('app.stats.recent_readings'), $recentReadings)
                ->description(__('app.stats.meters_with_data'))
                ->descriptionIcon('heroicon-m-eye')
                ->color('success'),
            
            Stat::make(__('app.stats.unpaid_invoices'), $unpaidInvoices)
                ->description(__('app.stats.pending_payment'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($unpaidInvoices > 0 ? 'warning' : 'success'),
            
            Stat::make(__('app.stats.current_month_readings'), $currentMonthReadings)
                ->description(__('app.stats.this_month'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
        ];
    }
}