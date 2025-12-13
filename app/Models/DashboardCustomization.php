<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardCustomization extends Model
{
    protected $fillable = [
        'user_id',
        'widget_configuration',
        'layout_configuration',
        'refresh_intervals',
    ];

    protected $casts = [
        'widget_configuration' => 'array',
        'layout_configuration' => 'array',
        'refresh_intervals' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the default widget configuration for superadmin dashboard
     */
    public static function getDefaultConfiguration(): array
    {
        return [
            'widgets' => [
                [
                    'class' => 'App\\Filament\\Widgets\\SubscriptionStatsWidget',
                    'position' => 1,
                    'size' => 'medium',
                    'refresh_interval' => 60,
                    'enabled' => true,
                ],
                [
                    'class' => 'App\\Filament\\Widgets\\OrganizationStatsWidget',
                    'position' => 2,
                    'size' => 'medium',
                    'refresh_interval' => 300,
                    'enabled' => true,
                ],
                [
                    'class' => 'App\\Filament\\Widgets\\SystemHealthWidget',
                    'position' => 3,
                    'size' => 'medium',
                    'refresh_interval' => 30,
                    'enabled' => true,
                ],
                [
                    'class' => 'App\\Filament\\Widgets\\ExpiringSubscriptionsWidget',
                    'position' => 4,
                    'size' => 'large',
                    'refresh_interval' => 300,
                    'enabled' => true,
                ],
                [
                    'class' => 'App\\Filament\\Widgets\\RecentActivityWidget',
                    'position' => 5,
                    'size' => 'medium',
                    'refresh_interval' => 60,
                    'enabled' => true,
                ],
                [
                    'class' => 'App\\Filament\\Widgets\\TopOrganizationsWidget',
                    'position' => 6,
                    'size' => 'large',
                    'refresh_interval' => 600,
                    'enabled' => true,
                ],
                [
                    'class' => 'App\\Filament\\Widgets\\PlatformUsageWidget',
                    'position' => 7,
                    'size' => 'large',
                    'refresh_interval' => 600,
                    'enabled' => true,
                ],
            ],
            'layout' => [
                'columns' => [
                    'sm' => 1,
                    'md' => 2,
                    'lg' => 3,
                ],
            ],
        ];
    }

    /**
     * Get available widget classes for the dashboard
     */
    public static function getAvailableWidgets(): array
    {
        return [
            'App\\Filament\\Widgets\\SubscriptionStatsWidget' => [
                'name' => 'Subscription Statistics',
                'description' => 'Display subscription counts and status breakdown',
                'category' => 'metrics',
                'default_size' => 'medium',
                'default_refresh' => 60,
            ],
            'App\\Filament\\Widgets\\OrganizationStatsWidget' => [
                'name' => 'Organization Statistics',
                'description' => 'Show organization counts and growth trends',
                'category' => 'metrics',
                'default_size' => 'medium',
                'default_refresh' => 300,
            ],
            'App\\Filament\\Widgets\\SystemHealthWidget' => [
                'name' => 'System Health',
                'description' => 'Real-time system health indicators',
                'category' => 'monitoring',
                'default_size' => 'medium',
                'default_refresh' => 30,
            ],
            'App\\Filament\\Widgets\\ExpiringSubscriptionsWidget' => [
                'name' => 'Expiring Subscriptions',
                'description' => 'Table of subscriptions expiring soon',
                'category' => 'tables',
                'default_size' => 'large',
                'default_refresh' => 300,
            ],
            'App\\Filament\\Widgets\\RecentActivityWidget' => [
                'name' => 'Recent Activity',
                'description' => 'Feed of recent system activities',
                'category' => 'activity',
                'default_size' => 'medium',
                'default_refresh' => 60,
            ],
            'App\\Filament\\Widgets\\TopOrganizationsWidget' => [
                'name' => 'Top Organizations',
                'description' => 'Chart of top organizations by metrics',
                'category' => 'charts',
                'default_size' => 'large',
                'default_refresh' => 600,
            ],
            'App\\Filament\\Widgets\\PlatformUsageWidget' => [
                'name' => 'Platform Usage',
                'description' => 'Platform growth and usage trends',
                'category' => 'charts',
                'default_size' => 'large',
                'default_refresh' => 600,
            ],
        ];
    }
}