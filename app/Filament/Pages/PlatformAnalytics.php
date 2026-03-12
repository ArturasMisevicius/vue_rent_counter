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

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 3;

    protected static ?string $title = null;

    protected string $view = 'filament.pages.platform-analytics';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperadmin(), 403);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('platform_analytics.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('platform_analytics.navigation.label');
    }

    public function getTitle(): string
    {
        return __('platform_analytics.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label(__('platform_analytics.actions.export_pdf'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->action(function () {
                    // TODO: Implement PDF export in task 10.5
                    return response()->streamDownload(function () {
                        echo $this->generateExecutiveSummary();
                    }, 'platform-analytics-'.now()->format('Y-m-d-His').'.txt');
                }),

            Action::make('exportCsv')
                ->label(__('platform_analytics.actions.export_csv'))
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->action(function () {
                    // TODO: Implement CSV export in task 10.5
                    return response()->streamDownload(function () {
                        echo $this->generateCsvData();
                    }, 'platform-analytics-'.now()->format('Y-m-d-His').'.csv');
                }),

            Action::make('refresh')
                ->label(__('platform_analytics.actions.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    // Clear all analytics caches
                    $locale = app()->getLocale();
                    $cacheKeys = [
                        'analytics_organization_growth',
                        "analytics_organization_plan_distribution_{$locale}",
                        'analytics_organization_active_inactive',
                        'analytics_top_organizations',
                        'analytics_subscription_renewal_rate',
                        "analytics_subscription_expiry_forecast_{$locale}",
                        'analytics_subscription_plan_changes',
                        "analytics_subscription_lifecycle_{$locale}",
                        'analytics_usage_totals',
                        'analytics_usage_growth',
                        'analytics_feature_usage',
                        'analytics_peak_activity',
                        "analytics_users_by_role_{$locale}",
                        'analytics_active_users',
                        "analytics_login_frequency_{$locale}",
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
        $locale = app()->getLocale();

        return Cache::remember("analytics_organization_plan_distribution_{$locale}", 3600, function () {
            $distribution = Organization::select('plan', DB::raw('count(*) as count'))
                ->groupBy('plan')
                ->get()
                ->pluck('count', 'plan')
                ->toArray();

            $labels = [];
            $data = [];
            foreach ($distribution as $plan => $count) {
                $labels[] = $this->getPlanLabel($plan);
                $data[] = $count;
            }

            return [
                'labels' => $labels,
                'data' => $data,
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
                ->map(fn ($org) => [
                    'name' => $org->name,
                    'count' => $org->properties_count,
                ])
                ->toArray();

            // Top by users
            $byUsers = Organization::withCount('users')
                ->orderBy('users_count', 'desc')
                ->limit(10)
                ->get()
                ->map(fn ($org) => [
                    'name' => $org->name,
                    'count' => $org->users_count,
                ])
                ->toArray();

            // Top by invoices
            $byInvoices = Organization::withCount('invoices')
                ->orderBy('invoices_count', 'desc')
                ->limit(10)
                ->get()
                ->map(fn ($org) => [
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
        $locale = app()->getLocale();

        return Cache::remember("analytics_subscription_expiry_forecast_{$locale}", 3600, function () {
            $forecast = [];

            for ($i = 0; $i < 90; $i += 7) {
                $startDate = now()->addDays($i);
                $endDate = now()->addDays($i + 7);

                $count = Organization::whereBetween('subscription_ends_at', [$startDate, $endDate])
                    ->count();

                $weekNumber = (int) (($i / 7) + 1);
                $forecast[] = [
                    'week' => __('platform_analytics.week', ['number' => $weekNumber]),
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
                $monthExpression = $this->getPeriodExpression('monthly');

                // Get plan changes from activity logs
                $changes = DB::table('organization_activity_logs')
                    ->where('action', 'plan_changed')
                    ->where('created_at', '>=', now()->subMonths(6))
                    ->selectRaw("{$monthExpression} as month, COUNT(*) as count")
                    ->groupByRaw($monthExpression)
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
        $locale = app()->getLocale();

        return Cache::remember("analytics_subscription_lifecycle_{$locale}", 3600, function () {
            $active = Organization::where('subscription_ends_at', '>', now())->count();
            $expiringSoon = Organization::whereBetween('subscription_ends_at', [now(), now()->addDays(14)])->count();
            $expired = Organization::where('subscription_ends_at', '<=', now())->count();
            $onTrial = Organization::whereNotNull('trial_ends_at')
                ->where('trial_ends_at', '>', now())
                ->count();

            return [
                'labels' => [
                    __('platform_analytics.lifecycle.active'),
                    __('platform_analytics.lifecycle.expiring_soon'),
                    __('platform_analytics.lifecycle.expired'),
                    __('platform_analytics.lifecycle.on_trial'),
                ],
                'data' => [$active, $expiringSoon, $expired, $onTrial],
            ];
        });
    }

    /**
     * Generate executive summary for PDF export
     */
    protected function generateExecutiveSummary(): string
    {
        $summary = __('platform_analytics.report.title')."\n";
        $summary .= __('platform_analytics.report.generated', [
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ])."\n";
        $summary .= str_repeat('=', 80)."\n\n";

        // Organization Analytics
        $summary .= __('platform_analytics.report.sections.organization')."\n";
        $summary .= str_repeat('-', 80)."\n";
        $orgAnalytics = $this->getOrganizationAnalytics();
        $summary .= __('platform_analytics.report.labels.total_organizations').': '.Organization::count()."\n";
        $summary .= __('platform_analytics.report.labels.active').': '.$orgAnalytics['activeInactive']['active']."\n";
        $summary .= __('platform_analytics.report.labels.inactive').': '.$orgAnalytics['activeInactive']['inactive']."\n\n";

        $summary .= __('platform_analytics.report.labels.plan_distribution')."\n";
        foreach ($orgAnalytics['planDistribution']['labels'] as $index => $plan) {
            $count = $orgAnalytics['planDistribution']['data'][$index];
            $summary .= "  {$plan}: {$count}\n";
        }
        $summary .= "\n";

        // Subscription Analytics
        $summary .= __('platform_analytics.report.sections.subscription')."\n";
        $summary .= str_repeat('-', 80)."\n";
        $subAnalytics = $this->getSubscriptionAnalytics();
        $summary .= __('platform_analytics.report.labels.renewal_rate').': '.$subAnalytics['renewalRate']['rate']."%\n";
        $summary .= __('platform_analytics.report.labels.renewed').': '.$subAnalytics['renewalRate']['renewed']."\n";
        $summary .= __('platform_analytics.report.labels.expired').': '.$subAnalytics['renewalRate']['expired']."\n\n";

        // Usage Analytics
        $summary .= __('platform_analytics.report.sections.usage')."\n";
        $summary .= str_repeat('-', 80)."\n";
        $usageAnalytics = $this->getUsageAnalytics();
        $summary .= __('platform_analytics.report.labels.total_properties').': '.number_format($usageAnalytics['totals']['properties'])."\n";
        $summary .= __('platform_analytics.report.labels.total_buildings').': '.number_format($usageAnalytics['totals']['buildings'])."\n";
        $summary .= __('platform_analytics.report.labels.total_meters').': '.number_format($usageAnalytics['totals']['meters'])."\n";
        $summary .= __('platform_analytics.report.labels.total_invoices').': '.number_format($usageAnalytics['totals']['invoices'])."\n\n";

        // User Analytics
        $summary .= __('platform_analytics.report.sections.user')."\n";
        $summary .= str_repeat('-', 80)."\n";
        $userAnalytics = $this->getUserAnalytics();
        $summary .= __('platform_analytics.report.labels.total_users').': '.User::count()."\n";
        $summary .= __('platform_analytics.report.labels.active_last_7_days').': '.$userAnalytics['activeUsers']['last7Days']."\n";
        $summary .= __('platform_analytics.report.labels.active_last_30_days').': '.$userAnalytics['activeUsers']['last30Days']."\n";
        $summary .= __('platform_analytics.report.labels.active_last_90_days').': '.$userAnalytics['activeUsers']['last90Days']."\n\n";

        $summary .= __('platform_analytics.report.labels.users_by_role')."\n";
        foreach ($userAnalytics['byRole']['labels'] as $index => $role) {
            $count = $userAnalytics['byRole']['data'][$index];
            $summary .= "  {$role}: {$count}\n";
        }
        $summary .= "\n";

        // Top Organizations
        $summary .= __('platform_analytics.report.sections.top_organizations')."\n";
        $summary .= str_repeat('-', 80)."\n";
        $summary .= __('platform_analytics.report.labels.by_properties')."\n";
        foreach (array_slice($orgAnalytics['topOrganizations']['byProperties'], 0, 5) as $org) {
            $summary .= "  {$org['name']}: {$org['count']}\n";
        }
        $summary .= "\n";

        $summary .= __('platform_analytics.report.labels.by_users')."\n";
        foreach (array_slice($orgAnalytics['topOrganizations']['byUsers'], 0, 5) as $org) {
            $summary .= "  {$org['name']}: {$org['count']}\n";
        }
        $summary .= "\n";

        $summary .= __('platform_analytics.report.labels.by_invoices')."\n";
        foreach (array_slice($orgAnalytics['topOrganizations']['byInvoices'], 0, 5) as $org) {
            $summary .= "  {$org['name']}: {$org['count']}\n";
        }
        $summary .= "\n";

        $summary .= str_repeat('=', 80)."\n";
        $summary .= __('platform_analytics.report.labels.end_of_report')."\n";

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
                $periodExpression = $this->getPeriodExpression($period);

                $days = match ($period) {
                    'daily' => 30,
                    'weekly' => 84, // 12 weeks
                    'monthly' => 365, // 12 months
                };

                $properties = DB::table('properties')
                    ->selectRaw("{$periodExpression} as period, COUNT(*) as count")
                    ->where('created_at', '>=', now()->subDays($days))
                    ->groupByRaw($periodExpression)
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
                $hourExpression = $this->getHourExpression();

                $hourly = DB::table('organization_activity_logs')
                    ->selectRaw("{$hourExpression} as hour, COUNT(*) as count")
                    ->where('created_at', '>=', now()->subDays(30))
                    ->groupByRaw($hourExpression)
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

    private function getPeriodExpression(string $period): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => match ($period) {
                'daily' => "strftime('%Y-%m-%d', created_at)",
                'weekly' => "strftime('%Y-W%W', created_at)",
                default => "strftime('%Y-%m', created_at)",
            },
            'pgsql' => match ($period) {
                'daily' => "to_char(created_at, 'YYYY-MM-DD')",
                'weekly' => "to_char(created_at, 'IYYY-\"W\"IW')",
                default => "to_char(created_at, 'YYYY-MM')",
            },
            default => match ($period) {
                'daily' => "DATE_FORMAT(created_at, '%Y-%m-%d')",
                'weekly' => "DATE_FORMAT(created_at, '%x-W%v')",
                default => "DATE_FORMAT(created_at, '%Y-%m')",
            },
        };
    }

    private function getHourExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%H', created_at) AS INTEGER)",
            'pgsql' => 'EXTRACT(HOUR FROM created_at)::int',
            default => 'HOUR(created_at)',
        };
    }

    /**
     * Generate CSV data for export
     */
    protected function generateCsvData(): string
    {
        $csv = implode(',', [
            __('platform_analytics.csv.headers.category'),
            __('platform_analytics.csv.headers.metric'),
            __('platform_analytics.csv.headers.value'),
        ])."\n";

        // Organization Analytics
        $orgAnalytics = $this->getOrganizationAnalytics();
        $csv .= __('platform_analytics.csv.categories.organizations').','.__('platform_analytics.csv.metrics.total').','.Organization::count()."\n";
        $csv .= __('platform_analytics.csv.categories.organizations').','.__('platform_analytics.csv.metrics.active').','.$orgAnalytics['activeInactive']['active']."\n";
        $csv .= __('platform_analytics.csv.categories.organizations').','.__('platform_analytics.csv.metrics.inactive').','.$orgAnalytics['activeInactive']['inactive']."\n";

        foreach ($orgAnalytics['planDistribution']['labels'] as $index => $plan) {
            $count = $orgAnalytics['planDistribution']['data'][$index];
            $csv .= __('platform_analytics.csv.categories.organizations').','.__('platform_analytics.csv.metrics.plan', ['plan' => $plan]).",{$count}\n";
        }

        // Subscription Analytics
        $subAnalytics = $this->getSubscriptionAnalytics();
        $csv .= __('platform_analytics.csv.categories.subscriptions').','.__('platform_analytics.csv.metrics.renewal_rate').','.$subAnalytics['renewalRate']['rate']."%\n";
        $csv .= __('platform_analytics.csv.categories.subscriptions').','.__('platform_analytics.csv.metrics.renewed').','.$subAnalytics['renewalRate']['renewed']."\n";
        $csv .= __('platform_analytics.csv.categories.subscriptions').','.__('platform_analytics.csv.metrics.expired').','.$subAnalytics['renewalRate']['expired']."\n";

        // Usage Analytics
        $usageAnalytics = $this->getUsageAnalytics();
        $csv .= __('platform_analytics.csv.categories.usage').','.__('platform_analytics.csv.metrics.total_properties').','.$usageAnalytics['totals']['properties']."\n";
        $csv .= __('platform_analytics.csv.categories.usage').','.__('platform_analytics.csv.metrics.total_buildings').','.$usageAnalytics['totals']['buildings']."\n";
        $csv .= __('platform_analytics.csv.categories.usage').','.__('platform_analytics.csv.metrics.total_meters').','.$usageAnalytics['totals']['meters']."\n";
        $csv .= __('platform_analytics.csv.categories.usage').','.__('platform_analytics.csv.metrics.total_invoices').','.$usageAnalytics['totals']['invoices']."\n";

        // User Analytics
        $userAnalytics = $this->getUserAnalytics();
        $csv .= __('platform_analytics.csv.categories.users').','.__('platform_analytics.csv.metrics.total').','.User::count()."\n";
        $csv .= __('platform_analytics.csv.categories.users').','.__('platform_analytics.csv.metrics.active_last_7_days').','.$userAnalytics['activeUsers']['last7Days']."\n";
        $csv .= __('platform_analytics.csv.categories.users').','.__('platform_analytics.csv.metrics.active_last_30_days').','.$userAnalytics['activeUsers']['last30Days']."\n";
        $csv .= __('platform_analytics.csv.categories.users').','.__('platform_analytics.csv.metrics.active_last_90_days').','.$userAnalytics['activeUsers']['last90Days']."\n";

        foreach ($userAnalytics['byRole']['labels'] as $index => $role) {
            $count = $userAnalytics['byRole']['data'][$index];
            $csv .= __('platform_analytics.csv.categories.users').','.__('platform_analytics.csv.metrics.role', ['role' => $role]).",{$count}\n";
        }

        // Top Organizations
        $csv .= "\n".__('platform_analytics.csv.sections.top_by_properties')."\n";
        $csv .= implode(',', [
            __('platform_analytics.csv.headers.rank'),
            __('platform_analytics.csv.headers.organization'),
            __('platform_analytics.csv.headers.count'),
        ])."\n";
        foreach (array_slice($orgAnalytics['topOrganizations']['byProperties'], 0, 10) as $index => $org) {
            $csv .= ($index + 1).",\"{$org['name']}\",{$org['count']}\n";
        }

        $csv .= "\n".__('platform_analytics.csv.sections.top_by_users')."\n";
        $csv .= implode(',', [
            __('platform_analytics.csv.headers.rank'),
            __('platform_analytics.csv.headers.organization'),
            __('platform_analytics.csv.headers.count'),
        ])."\n";
        foreach (array_slice($orgAnalytics['topOrganizations']['byUsers'], 0, 10) as $index => $org) {
            $csv .= ($index + 1).",\"{$org['name']}\",{$org['count']}\n";
        }

        $csv .= "\n".__('platform_analytics.csv.sections.top_by_invoices')."\n";
        $csv .= implode(',', [
            __('platform_analytics.csv.headers.rank'),
            __('platform_analytics.csv.headers.organization'),
            __('platform_analytics.csv.headers.count'),
        ])."\n";
        foreach (array_slice($orgAnalytics['topOrganizations']['byInvoices'], 0, 10) as $index => $org) {
            $csv .= ($index + 1).",\"{$org['name']}\",{$org['count']}\n";
        }

        return $csv;
    }

    /**
     * Get users by role
     */
    protected function getUsersByRole(): array
    {
        $locale = app()->getLocale();

        return Cache::remember("analytics_users_by_role_{$locale}", 3600, function () {
            $users = User::select('role', DB::raw('COUNT(*) as count'))
                ->groupBy('role')
                ->get();

            return [
                'labels' => $users
                    ->pluck('role')
                    ->map(fn ($role) => $this->getRoleLabel($role))
                    ->toArray(),
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
        $locale = app()->getLocale();

        return Cache::remember("analytics_login_frequency_{$locale}", 3600, function () {
            // Count users by login frequency in last 30 days
            $daily = User::where('last_login_at', '>=', now()->subDays(1))->count();
            $weekly = User::whereBetween('last_login_at', [now()->subDays(7), now()->subDays(1)])->count();
            $monthly = User::whereBetween('last_login_at', [now()->subDays(30), now()->subDays(7)])->count();
            $inactive = User::where('last_login_at', '<', now()->subDays(30))
                ->orWhereNull('last_login_at')
                ->count();

            return [
                'labels' => [
                    __('platform_analytics.login_frequency.daily'),
                    __('platform_analytics.login_frequency.weekly'),
                    __('platform_analytics.login_frequency.monthly'),
                    __('platform_analytics.login_frequency.inactive'),
                ],
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

    private function getPlanLabel(string|BackedEnum $plan): string
    {
        $plan = $plan instanceof BackedEnum ? (string) $plan->value : $plan;
        $key = "shared.superadmin.subscription.plan.{$plan}";
        $label = __($key);

        if ($label === $key) {
            return ucfirst($plan);
        }

        return $label;
    }

    private function getRoleLabel(string|BackedEnum $role): string
    {
        $role = $role instanceof BackedEnum ? (string) $role->value : $role;
        $key = "filament.resources.platform_users.roles.{$role}";
        $label = __($key);

        if ($label === $key) {
            return ucfirst($role);
        }
        return $label;
    }
}
