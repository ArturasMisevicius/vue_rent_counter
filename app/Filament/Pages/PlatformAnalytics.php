<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class PlatformAnalytics extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Platform Analytics';

    protected string $view = 'filament.pages.platform-analytics';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperadmin(), 403);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Export to PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->action(function () {
                    // TODO: Implement PDF export in task 10.5
                    return response()->streamDownload(function () {
                        echo $this->generateExecutiveSummary();
                    }, 'platform-analytics-' . now()->format('Y-m-d-His') . '.txt');
                }),

            Action::make('exportCsv')
                ->label('Export to CSV')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->action(function () {
                    // TODO: Implement CSV export in task 10.5
                    return response()->streamDownload(function () {
                        echo $this->generateCsvData();
                    }, 'platform-analytics-' . now()->format('Y-m-d-His') . '.csv');
                }),

            Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    // Clear all analytics caches
                    $cacheKeys = [
                        'analytics_organization_growth',
                        'analytics_organization_plan_distribution',
                        'analytics_organization_active_inactive',
                        'analytics_top_organizations',
                        'analytics_subscription_renewal_rate',
                        'analytics_subscription_expiry_forecast',
                        'analytics_subscription_plan_changes',
                        'analytics_subscription_lifecycle',
                        'analytics_usage_totals',
                        'analytics_usage_growth',
                        'analytics_feature_usage',
                        'analytics_peak_activity',
                        'analytics_users_by_role',
                        'analytics_active_users',
                        'analytics_login_frequency',
                        'analytics_user_growth',
                    ];
                    
                    foreach ($cacheKeys as $key) {
                        Cache::forget($key);
                    }
                }),
        ];
    }

    /**
     * Get organization analytics data
     */
    public function getOrganizationAnalytics(): array
    {
        return [
            'growth' => $this->getOrganizationGrowth(),
            'planDistribution' => $this->getPlanDistribution(),
            'activeInactive' => $this->getActiveInactiveOrganizations(),
            'topOrganizations' => $this->getTopOrganizations(),
        ];
    }

    /**
     * Get subscription analytics data
     */
    public function getSubscriptionAnalytics(): array
    {
        return [
            'renewalRate' => $this->getRenewalRate(),
            'expiryForecast' => $this->getExpiryForecast(),
            'planChanges' => $this->getPlanChangeTrends(),
            'lifecycle' => $this->getSubscriptionLifecycle(),
        ];
    }

    /**
     * Get usage analytics data
     */
    public function getUsageAnalytics(): array
    {
        return [
            'totals' => $this->getUsageTotals(),
            'growth' => $this->getUsageGrowth(),
            'featureUsage' => $this->getFeatureUsage(),
            'peakActivity' => $this->getPeakActivityTimes(),
        ];
    }

    /**
     * Get user analytics data
     */
    public function getUserAnalytics(): array
    {
        return [
            'byRole' => $this->getUsersByRole(),
            'activeUsers' => $this->getActiveUsers(),
            'loginFrequency' => $this->getLoginFrequency(),
            'userGrowth' => $this->getUserGrowth(),
        ];
    }

    /**
     * Get organization growth over time (last 12 months)
     */
    protected function getOrganizationGrowth(): array
    {
        return Cache::remember('analytics_organization_growth', 3600, function () {
            $months = [];
            $data = [];

            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $months[] = $date->format('M Y');
                
                $count = Organization::where('created_at', '<=', $date->endOfMonth())
                    ->count();
                
                $data[] = $count;
            }

            return [
                'labels' => $months,
                'data' => $data,
            ];
        });
    }

    /**
     * Get plan distribution
     */
    protected function getPlanDistribution(): array
    {
        return Cache::remember('analytics_organization_plan_distribution', 3600, function () {
            $distribution = Organization::select('plan', DB::raw('count(*) as count'))
                ->groupBy('plan')
                ->get()
                ->pluck('count', 'plan')
                ->toArray();

            return [
                'labels' => array_keys($distribution),
                'data' => array_values($distribution),
            ];
        });
    }

    /**
     * Get active vs inactive organizations
     */
    protected function getActiveInactiveOrganizations(): array
    {
        return Cache::remember('analytics_organization_active_inactive', 3600, function () {
            $active = Organization::where('is_active', true)
                ->whereNull('suspended_at')
                ->count();
            
            $inactive = Organization::where('is_active', false)
                ->orWhereNotNull('suspended_at')
                ->count();

            return [
                'active' => $active,
                'inactive' => $inactive,
            ];
        });
    }

    /**
     * Get top organizations by properties, users, and invoices
     */
    protected function getTopOrganizations(): array
    {
        return Cache::remember('analytics_top_organizations', 3600, function () {
            // Top by properties
            $byProperties = Organization::withCount('properties')
                ->orderBy('properties_count', 'desc')
                ->limit(10)
                ->get()
                ->map(fn($org) => [
                    'name' => $org->name,
                    'count' => $org->properties_count,
                ])
                ->toArray();

            // Top by users
            $byUsers = Organization::withCount('users')
                ->orderBy('users_count', 'desc')
                ->limit(10)
                ->get()
                ->map(fn($org) => [
                    'name' => $org->name,
                    'count' => $org->users_count,
                ])
                ->toArray();

            // Top by invoices
            $byInvoices = Organization::withCount('invoices')
                ->orderBy('invoices_count', 'desc')
                ->limit(10)
                ->get()
                ->map(fn($org) => [
                    'name' => $org->name,
                    'count' => $org->invoices_count,
                ])
                ->toArray();

            return [
                'byProperties' => $byProperties,
                'byUsers' => $byUsers,
                'byInvoices' => $byInvoices,
            ];
        });
    }

    /**
     * Get renewal rate (percentage of subscriptions renewed in last 90 days)
     */
    protected function getRenewalRate(): array
    {
        return Cache::remember('analytics_subscription_renewal_rate', 3600, function () {
            $ninetyDaysAgo = now()->subDays(90);
            
            // Count organizations that had subscriptions expiring in the last 90 days
            $expiring = Organization::where('subscription_ends_at', '>=', $ninetyDaysAgo)
                ->where('subscription_ends_at', '<=', now())
                ->count();
            
            // Count those that were renewed (subscription_ends_at is now in the future)
            $renewed = Organization::where('subscription_ends_at', '>', now())
                ->where('updated_at', '>=', $ninetyDaysAgo)
                ->count();
            
            $rate = $expiring > 0 ? round(($renewed / $expiring) * 100, 2) : 0;
            
            return [
                'rate' => $rate,
                'renewed' => $renewed,
                'expired' => $expiring - $renewed,
            ];
        });
    }

    /**
     * Get expiry forecast for next 90 days
     */
    protected function getExpiryForecast(): array
    {
        return Cache::remember('analytics_subscription_expiry_forecast', 3600, function () {
            $forecast = [];
            
            for ($i = 0; $i < 90; $i += 7) {
                $startDate = now()->addDays($i);
                $endDate = now()->addDays($i + 7);
                
                $count = Organization::whereBetween('subscription_ends_at', [$startDate, $endDate])
                    ->count();
                
                $forecast[] = [
                    'week' => 'Week ' . (($i / 7) + 1),
                    'count' => $count,
                ];
            }
            
            return $forecast;
        });
    }

    /**
     * Get plan upgrade/downgrade trends
     */
    protected function getPlanChangeTrends(): array
    {
        return Cache::remember('analytics_subscription_plan_changes', 3600, function () {
            try {
                // Get plan changes from activity logs
                $changes = DB::table('organization_activity_logs')
                    ->where('action', 'plan_changed')
                    ->where('created_at', '>=', now()->subMonths(6))
                    ->select(
                        DB::raw("strftime('%Y-%m', created_at) as month"),
                        DB::raw('COUNT(*) as count')
                    )
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get();
                
                return [
                    'labels' => $changes->pluck('month')->toArray(),
                    'data' => $changes->pluck('count')->toArray(),
                ];
            } catch (\Exception $e) {
                // Return empty data if table doesn't exist
                return [
                    'labels' => [],
                    'data' => [],
                ];
            }
        });
    }

    /**
     * Get subscription lifecycle chart
     */
    protected function getSubscriptionLifecycle(): array
    {
        return Cache::remember('analytics_subscription_lifecycle', 3600, function () {
            $active = Organization::where('subscription_ends_at', '>', now())->count();
            $expiringSoon = Organization::whereBetween('subscription_ends_at', [now(), now()->addDays(14)])->count();
            $expired = Organization::where('subscription_ends_at', '<=', now())->count();
            $onTrial = Organization::whereNotNull('trial_ends_at')
                ->where('trial_ends_at', '>', now())
                ->count();
            
            return [
                'labels' => ['Active', 'Expiring Soon', 'Expired', 'On Trial'],
                'data' => [$active, $expiringSoon, $expired, $onTrial],
            ];
        });
    }

    /**
     * Generate executive summary for PDF export
     */
    protected function generateExecutiveSummary(): string
    {
        $summary = "Platform Analytics Executive Summary\n";
        $summary .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n";
        $summary .= str_repeat('=', 80) . "\n\n";

        // Organization Analytics
        $summary .= "ORGANIZATION ANALYTICS\n";
        $summary .= str_repeat('-', 80) . "\n";
        $orgAnalytics = $this->getOrganizationAnalytics();
        $summary .= "Total Organizations: " . Organization::count() . "\n";
        $summary .= "Active: " . $orgAnalytics['activeInactive']['active'] . "\n";
        $summary .= "Inactive: " . $orgAnalytics['activeInactive']['inactive'] . "\n\n";

        $summary .= "Plan Distribution:\n";
        foreach ($orgAnalytics['planDistribution']['labels'] as $index => $plan) {
            $count = $orgAnalytics['planDistribution']['data'][$index];
            $summary .= "  {$plan}: {$count}\n";
        }
        $summary .= "\n";

        // Subscription Analytics
        $summary .= "SUBSCRIPTION ANALYTICS\n";
        $summary .= str_repeat('-', 80) . "\n";
        $subAnalytics = $this->getSubscriptionAnalytics();
        $summary .= "Renewal Rate: " . $subAnalytics['renewalRate']['rate'] . "%\n";
        $summary .= "Renewed: " . $subAnalytics['renewalRate']['renewed'] . "\n";
        $summary .= "Expired: " . $subAnalytics['renewalRate']['expired'] . "\n\n";

        // Usage Analytics
        $summary .= "USAGE ANALYTICS\n";
        $summary .= str_repeat('-', 80) . "\n";
        $usageAnalytics = $this->getUsageAnalytics();
        $summary .= "Total Properties: " . number_format($usageAnalytics['totals']['properties']) . "\n";
        $summary .= "Total Buildings: " . number_format($usageAnalytics['totals']['buildings']) . "\n";
        $summary .= "Total Meters: " . number_format($usageAnalytics['totals']['meters']) . "\n";
        $summary .= "Total Invoices: " . number_format($usageAnalytics['totals']['invoices']) . "\n\n";

        // User Analytics
        $summary .= "USER ANALYTICS\n";
        $summary .= str_repeat('-', 80) . "\n";
        $userAnalytics = $this->getUserAnalytics();
        $summary .= "Total Users: " . User::count() . "\n";
        $summary .= "Active (Last 7 Days): " . $userAnalytics['activeUsers']['last7Days'] . "\n";
        $summary .= "Active (Last 30 Days): " . $userAnalytics['activeUsers']['last30Days'] . "\n";
        $summary .= "Active (Last 90 Days): " . $userAnalytics['activeUsers']['last90Days'] . "\n\n";

        $summary .= "Users by Role:\n";
        foreach ($userAnalytics['byRole']['labels'] as $index => $role) {
            $count = $userAnalytics['byRole']['data'][$index];
            $summary .= "  {$role}: {$count}\n";
        }
        $summary .= "\n";

        // Top Organizations
        $summary .= "TOP ORGANIZATIONS\n";
        $summary .= str_repeat('-', 80) . "\n";
        $summary .= "By Properties:\n";
        foreach (array_slice($orgAnalytics['topOrganizations']['byProperties'], 0, 5) as $org) {
            $summary .= "  {$org['name']}: {$org['count']}\n";
        }
        $summary .= "\n";

        $summary .= "By Users:\n";
        foreach (array_slice($orgAnalytics['topOrganizations']['byUsers'], 0, 5) as $org) {
            $summary .= "  {$org['name']}: {$org['count']}\n";
        }
        $summary .= "\n";

        $summary .= "By Invoices:\n";
        foreach (array_slice($orgAnalytics['topOrganizations']['byInvoices'], 0, 5) as $org) {
            $summary .= "  {$org['name']}: {$org['count']}\n";
        }
        $summary .= "\n";

        $summary .= str_repeat('=', 80) . "\n";
        $summary .= "End of Report\n";

        return $summary;
    }

    /**
     * Get usage totals
     */
    protected function getUsageTotals(): array
    {
        return Cache::remember('analytics_usage_totals', 3600, function () {
            return [
                'properties' => Property::count(),
                'buildings' => Building::count(),
                'meters' => Meter::count(),
                'invoices' => Invoice::count(),
            ];
        });
    }

    /**
     * Get usage growth trends
     */
    protected function getUsageGrowth(): array
    {
        return Cache::remember('analytics_usage_growth', 3600, function () {
            $periods = ['daily', 'weekly', 'monthly'];
            $growth = [];

            foreach ($periods as $period) {
                $dateFormat = match($period) {
                    'daily' => '%Y-%m-%d',
                    'weekly' => '%Y-W%W',
                    'monthly' => '%Y-%m',
                };

                $days = match($period) {
                    'daily' => 30,
                    'weekly' => 84, // 12 weeks
                    'monthly' => 365, // 12 months
                };

                // Use strftime for SQLite compatibility
                $properties = DB::table('properties')
                    ->select(
                        DB::raw("strftime('{$dateFormat}', created_at) as period"),
                        DB::raw('COUNT(*) as count')
                    )
                    ->where('created_at', '>=', now()->subDays($days))
                    ->groupBy('period')
                    ->orderBy('period')
                    ->get();

                $growth[$period] = [
                    'labels' => $properties->pluck('period')->toArray(),
                    'data' => $properties->pluck('count')->toArray(),
                ];
            }

            return $growth;
        });
    }

    /**
     * Get feature usage heatmap data
     */
    protected function getFeatureUsage(): array
    {
        return Cache::remember('analytics_feature_usage', 3600, function () {
            try {
                // Get action counts from activity logs
                $actions = DB::table('organization_activity_logs')
                    ->select('action', DB::raw('COUNT(*) as count'))
                    ->where('created_at', '>=', now()->subDays(30))
                    ->groupBy('action')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get();

                return [
                    'labels' => $actions->pluck('action')->toArray(),
                    'data' => $actions->pluck('count')->toArray(),
                ];
            } catch (\Exception $e) {
                // Return empty data if table doesn't exist
                return [
                    'labels' => [],
                    'data' => [],
                ];
            }
        });
    }

    /**
     * Get peak activity times
     */
    protected function getPeakActivityTimes(): array
    {
        return Cache::remember('analytics_peak_activity', 3600, function () {
            try {
                $hourly = DB::table('organization_activity_logs')
                    ->select(
                        DB::raw("CAST(strftime('%H', created_at) AS INTEGER) as hour"),
                        DB::raw('COUNT(*) as count')
                    )
                    ->where('created_at', '>=', now()->subDays(30))
                    ->groupBy('hour')
                    ->orderBy('hour')
                    ->get();

                $hours = [];
                $counts = [];
                
                for ($i = 0; $i < 24; $i++) {
                    $hours[] = sprintf('%02d:00', $i);
                    $hourData = $hourly->firstWhere('hour', $i);
                    $counts[] = $hourData ? $hourData->count : 0;
                }

                return [
                    'labels' => $hours,
                    'data' => $counts,
                ];
            } catch (\Exception $e) {
                // Return empty data if table doesn't exist
                $hours = [];
                for ($i = 0; $i < 24; $i++) {
                    $hours[] = sprintf('%02d:00', $i);
                }
                return [
                    'labels' => $hours,
                    'data' => array_fill(0, 24, 0),
                ];
            }
        });
    }

    /**
     * Generate CSV data for export
     */
    protected function generateCsvData(): string
    {
        $csv = "Category,Metric,Value\n";
        
        // Organization Analytics
        $orgAnalytics = $this->getOrganizationAnalytics();
        $csv .= "Organizations,Total," . Organization::count() . "\n";
        $csv .= "Organizations,Active," . $orgAnalytics['activeInactive']['active'] . "\n";
        $csv .= "Organizations,Inactive," . $orgAnalytics['activeInactive']['inactive'] . "\n";
        
        foreach ($orgAnalytics['planDistribution']['labels'] as $index => $plan) {
            $count = $orgAnalytics['planDistribution']['data'][$index];
            $csv .= "Organizations,Plan - {$plan},{$count}\n";
        }

        // Subscription Analytics
        $subAnalytics = $this->getSubscriptionAnalytics();
        $csv .= "Subscriptions,Renewal Rate," . $subAnalytics['renewalRate']['rate'] . "%\n";
        $csv .= "Subscriptions,Renewed," . $subAnalytics['renewalRate']['renewed'] . "\n";
        $csv .= "Subscriptions,Expired," . $subAnalytics['renewalRate']['expired'] . "\n";

        // Usage Analytics
        $usageAnalytics = $this->getUsageAnalytics();
        $csv .= "Usage,Total Properties," . $usageAnalytics['totals']['properties'] . "\n";
        $csv .= "Usage,Total Buildings," . $usageAnalytics['totals']['buildings'] . "\n";
        $csv .= "Usage,Total Meters," . $usageAnalytics['totals']['meters'] . "\n";
        $csv .= "Usage,Total Invoices," . $usageAnalytics['totals']['invoices'] . "\n";

        // User Analytics
        $userAnalytics = $this->getUserAnalytics();
        $csv .= "Users,Total," . User::count() . "\n";
        $csv .= "Users,Active (7 Days)," . $userAnalytics['activeUsers']['last7Days'] . "\n";
        $csv .= "Users,Active (30 Days)," . $userAnalytics['activeUsers']['last30Days'] . "\n";
        $csv .= "Users,Active (90 Days)," . $userAnalytics['activeUsers']['last90Days'] . "\n";
        
        foreach ($userAnalytics['byRole']['labels'] as $index => $role) {
            $count = $userAnalytics['byRole']['data'][$index];
            $csv .= "Users,Role - {$role},{$count}\n";
        }

        // Top Organizations
        $csv .= "\nTop Organizations by Properties\n";
        $csv .= "Rank,Organization,Count\n";
        foreach (array_slice($orgAnalytics['topOrganizations']['byProperties'], 0, 10) as $index => $org) {
            $csv .= ($index + 1) . ",\"{$org['name']}\",{$org['count']}\n";
        }

        $csv .= "\nTop Organizations by Users\n";
        $csv .= "Rank,Organization,Count\n";
        foreach (array_slice($orgAnalytics['topOrganizations']['byUsers'], 0, 10) as $index => $org) {
            $csv .= ($index + 1) . ",\"{$org['name']}\",{$org['count']}\n";
        }

        $csv .= "\nTop Organizations by Invoices\n";
        $csv .= "Rank,Organization,Count\n";
        foreach (array_slice($orgAnalytics['topOrganizations']['byInvoices'], 0, 10) as $index => $org) {
            $csv .= ($index + 1) . ",\"{$org['name']}\",{$org['count']}\n";
        }

        return $csv;
    }

    /**
     * Get users by role
     */
    protected function getUsersByRole(): array
    {
        return Cache::remember('analytics_users_by_role', 3600, function () {
            $users = User::select('role', DB::raw('COUNT(*) as count'))
                ->groupBy('role')
                ->get();

            return [
                'labels' => $users->pluck('role')->map(fn($role) => ucfirst($role))->toArray(),
                'data' => $users->pluck('count')->toArray(),
            ];
        });
    }

    /**
     * Get active users for different time periods
     */
    protected function getActiveUsers(): array
    {
        return Cache::remember('analytics_active_users', 3600, function () {
            $last7Days = User::where('last_login_at', '>=', now()->subDays(7))->count();
            $last30Days = User::where('last_login_at', '>=', now()->subDays(30))->count();
            $last90Days = User::where('last_login_at', '>=', now()->subDays(90))->count();

            return [
                'last7Days' => $last7Days,
                'last30Days' => $last30Days,
                'last90Days' => $last90Days,
            ];
        });
    }

    /**
     * Get login frequency distribution
     */
    protected function getLoginFrequency(): array
    {
        return Cache::remember('analytics_login_frequency', 3600, function () {
            // Count users by login frequency in last 30 days
            $daily = User::where('last_login_at', '>=', now()->subDays(1))->count();
            $weekly = User::whereBetween('last_login_at', [now()->subDays(7), now()->subDays(1)])->count();
            $monthly = User::whereBetween('last_login_at', [now()->subDays(30), now()->subDays(7)])->count();
            $inactive = User::where('last_login_at', '<', now()->subDays(30))
                ->orWhereNull('last_login_at')
                ->count();

            return [
                'labels' => ['Daily', 'Weekly', 'Monthly', 'Inactive'],
                'data' => [$daily, $weekly, $monthly, $inactive],
            ];
        });
    }

    /**
     * Get user growth trends
     */
    protected function getUserGrowth(): array
    {
        return Cache::remember('analytics_user_growth', 3600, function () {
            $months = [];
            $data = [];

            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $months[] = $date->format('M Y');
                
                $count = User::where('created_at', '<=', $date->endOfMonth())
                    ->count();
                
                $data[] = $count;
            }

            return [
                'labels' => $months,
                'data' => $data,
            ];
        });
    }
}
