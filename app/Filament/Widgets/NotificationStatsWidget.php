<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\PlatformNotification;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NotificationStatsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    protected function getStats(): array
    {
        $totalNotifications = PlatformNotification::count();
        $sentNotifications = PlatformNotification::sent()->count();
        $scheduledNotifications = PlatformNotification::scheduled()->count();
        $failedNotifications = PlatformNotification::failed()->count();

        // Calculate delivery rate for sent notifications
        $totalRecipients = PlatformNotification::sent()
            ->withCount('recipients')
            ->get()
            ->sum('recipients_count');

        $successfulDeliveries = \App\Models\PlatformNotificationRecipient::where('delivery_status', 'sent')->count();
        
        $deliveryRate = $totalRecipients > 0 ? ($successfulDeliveries / $totalRecipients) * 100 : 0;

        return [
            Stat::make('Total Notifications', $totalNotifications)
                ->description('All platform notifications')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary')
                ->url(route('filament.admin.resources.platform-notifications.index')),

            Stat::make('Sent Notifications', $sentNotifications)
                ->description('Successfully sent')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->url(route('filament.admin.resources.platform-notifications.index')),

            Stat::make('Scheduled Notifications', $scheduledNotifications)
                ->description('Pending delivery')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(route('filament.admin.resources.platform-notifications.index')),

            Stat::make('Failed Notifications', $failedNotifications)
                ->description('Delivery failed')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->url(route('filament.admin.resources.platform-notifications.index')),

            Stat::make('Delivery Rate', number_format($deliveryRate, 1) . '%')
                ->description('Overall delivery success')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($deliveryRate >= 95 ? 'success' : ($deliveryRate >= 85 ? 'warning' : 'danger'))
                ->url(route('filament.admin.resources.platform-notifications.index')),
        ];
    }

    protected function getColumns(): int
    {
        return 5;
    }
}