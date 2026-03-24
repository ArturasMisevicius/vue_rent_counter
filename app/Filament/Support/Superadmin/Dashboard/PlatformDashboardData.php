<?php

namespace App\Filament\Support\Superadmin\Dashboard;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Filament\Support\Dashboard\DashboardCacheService;
use App\Models\Organization;
use App\Models\SecurityViolation;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PlatformDashboardData
{
    private const REVENUE_SERIES_COLORS = [
        'starter' => '#14b8a6',
        'basic' => '#2563eb',
        'professional' => '#f59e0b',
        'enterprise' => '#7c3aed',
        'custom' => '#ec4899',
    ];

    public function __construct(
        protected DashboardCacheService $dashboardCacheService,
    ) {}

    /**
     * @return array{
     *     metrics: array<int, array{
     *         label: string,
     *         value: string,
     *         icon: string,
     *         trend: string|null,
     *         trend_direction: string|null,
     *         trend_tone: string,
     *         value_tone: string
     *     }>,
     *     revenueByPlan: array{
     *         labels: array<int, string>,
     *         series: array<int, array{
     *             label: string,
     *             color: string,
     *             points: array<int, float>,
     *             formatted: array<int, string>
     *         }>
     *     },
     *     expiringSubscriptions: array{
     *         rows: array<int, array{organization: string, plan: string, expires_at: string}>,
     *         has_more: bool,
     *         view_all_url: string
     *     },
     *     recentSecurityViolations: array<int, array{type: string, ip_address: string, severity: string, occurred_ago: string}>,
     *     recentOrganizations: array{
     *         rows: array<int, array<string, string>>,
     *         export_url: string
     *     }
     * }
     */
    public function for(User $user): array
    {
        return $this->dashboardCacheService->remember(
            $user,
            'platform-dashboard',
            fn (): array => $this->buildData(),
        );
    }

    /**
     * @return array{
     *     columns: array<int, array{key: string, label: string}>,
     *     rows: array<int, array<string, string>>,
     *     title: string
     * }
     */
    public function recentOrganizationsExport(int $limit = 10): array
    {
        return [
            'title' => __('dashboard.platform_sections.recent_organizations'),
            'columns' => $this->recentOrganizationsColumns(),
            'rows' => $this->recentOrganizationsRows($limit),
        ];
    }

    /**
     * @return array{
     *     metrics: array<int, array{
     *         label: string,
     *         value: string,
     *         icon: string,
     *         trend: string|null,
     *         trend_direction: string|null,
     *         trend_tone: string,
     *         value_tone: string
     *     }>,
     *     revenueByPlan: array{
     *         labels: array<int, string>,
     *         series: array<int, array{
     *             label: string,
     *             color: string,
     *             points: array<int, float>,
     *             formatted: array<int, string>
     *         }>
     *     },
     *     expiringSubscriptions: array{
     *         rows: array<int, array{organization: string, plan: string, expires_at: string}>,
     *         has_more: bool,
     *         view_all_url: string
     *     },
     *     recentSecurityViolations: array<int, array{type: string, ip_address: string, severity: string, occurred_ago: string}>,
     *     recentOrganizations: array{
     *         rows: array<int, array<string, string>>,
     *         export_url: string
     *     }
     * }
     */
    private function buildData(): array
    {
        $currentMonth = [now()->startOfMonth(), now()->endOfMonth()];
        $organizationCount = Organization::query()->count();
        $previousOrganizationCount = Organization::query()
            ->where('created_at', '<=', now()->subMonth())
            ->count();
        $organizationTrend = $this->trendComparedToLastMonth($organizationCount, $previousOrganizationCount);
        $revenue = (float) SubscriptionPayment::query()
            ->whereBetween('paid_at', $currentMonth)
            ->sum('amount');
        $activeSubscriptions = Subscription::query()
            ->where('status', SubscriptionStatus::ACTIVE)
            ->count();
        $recentSecurityViolationsCount = SecurityViolation::query()
            ->where('occurred_at', '>=', now()->subDays(7))
            ->count();

        return [
            'metrics' => [
                [
                    'label' => __('dashboard.platform_metrics.total_organizations'),
                    'value' => (string) $organizationCount,
                    'icon' => 'heroicon-m-building-office-2',
                    'trend' => $organizationTrend['text'],
                    'trend_direction' => $organizationTrend['direction'],
                    'trend_tone' => $organizationTrend['tone'],
                    'value_tone' => 'default',
                ],
                [
                    'label' => __('dashboard.platform_metrics.active_subscriptions'),
                    'value' => (string) $activeSubscriptions,
                    'icon' => 'heroicon-m-credit-card',
                    'trend' => null,
                    'trend_direction' => null,
                    'trend_tone' => 'muted',
                    'value_tone' => 'default',
                ],
                [
                    'label' => __('dashboard.platform_metrics.platform_revenue_this_month'),
                    'value' => $this->formatCurrency($revenue),
                    'icon' => 'heroicon-m-banknotes',
                    'trend' => null,
                    'trend_direction' => null,
                    'trend_tone' => 'muted',
                    'value_tone' => 'default',
                ],
                [
                    'label' => __('dashboard.platform_metrics.security_violations_last_7_days'),
                    'value' => (string) $recentSecurityViolationsCount,
                    'icon' => 'heroicon-m-shield-exclamation',
                    'trend' => null,
                    'trend_direction' => null,
                    'trend_tone' => 'muted',
                    'value_tone' => $recentSecurityViolationsCount > 0 ? 'danger' : 'default',
                ],
            ],
            'revenueByPlan' => $this->revenueByPlanLastTwelveMonths(),
            'expiringSubscriptions' => $this->expiringSubscriptions(),
            'recentSecurityViolations' => $this->recentSecurityViolations(),
            'recentOrganizations' => [
                'rows' => $this->recentOrganizationsRows(),
                'export_url' => route('filament.admin.pages.platform-dashboard.recent-organizations-export'),
            ],
        ];
    }

    /**
     * @return array{direction: string, text: string, tone: string}
     */
    private function trendComparedToLastMonth(int $current, int $previous): array
    {
        if ($current === $previous) {
            return [
                'direction' => 'flat',
                'text' => __('dashboard.platform_trends.vs_last_month', ['percentage' => 0]),
                'tone' => 'muted',
            ];
        }

        $percentage = $previous === 0
            ? 100
            : (int) round((abs($current - $previous) / $previous) * 100);

        return [
            'direction' => $current > $previous ? 'up' : 'down',
            'text' => __('dashboard.platform_trends.vs_last_month', ['percentage' => $percentage]),
            'tone' => $current > $previous ? 'success' : 'danger',
        ];
    }

    /**
     * @return array{
     *     labels: array<int, string>,
     *     series: array<int, array{
     *         label: string,
     *         color: string,
     *         points: array<int, float>,
     *         formatted: array<int, string>
     *     }>
     * }
     */
    private function revenueByPlanLastTwelveMonths(): array
    {
        $windowStart = now()->startOfMonth()->subMonths(11);
        $monthKeys = collect(range(0, 11))
            ->map(fn (int $offset): string => $windowStart->copy()->addMonths($offset)->format('Y-m'))
            ->all();
        $labels = collect($monthKeys)
            ->map(fn (string $monthKey): string => Carbon::createFromFormat('Y-m', $monthKey)
                ->locale(app()->getLocale())
                ->translatedFormat('M'))
            ->all();

        $payments = SubscriptionPayment::query()
            ->select(['id', 'subscription_id', 'amount', 'currency', 'paid_at'])
            ->whereBetween('paid_at', [$windowStart, now()->endOfMonth()])
            ->with(['subscription:id,plan'])
            ->get();

        $plans = collect([
            SubscriptionPlan::BASIC,
            SubscriptionPlan::PROFESSIONAL,
            SubscriptionPlan::ENTERPRISE,
        ])->merge(
            $payments
                ->map(fn (SubscriptionPayment $payment): ?SubscriptionPlan => $payment->subscription?->plan)
                ->filter(),
        )
            ->unique(fn (SubscriptionPlan $plan): string => $plan->value)
            ->values();

        return [
            'labels' => $labels,
            'series' => $plans
                ->map(function (SubscriptionPlan $plan) use ($payments, $monthKeys): array {
                    $points = collect($monthKeys)
                        ->map(function (string $monthKey) use ($payments, $plan): float {
                            return (float) $payments
                                ->filter(function (SubscriptionPayment $payment) use ($monthKey, $plan): bool {
                                    return $payment->subscription?->plan === $plan
                                        && $payment->paid_at?->format('Y-m') === $monthKey;
                                })
                                ->sum('amount');
                        })
                        ->all();

                    return [
                        'label' => $plan->label(),
                        'color' => self::REVENUE_SERIES_COLORS[$plan->value] ?? '#334155',
                        'points' => $points,
                        'formatted' => array_map(
                            fn (float $amount): string => $this->formatCurrency($amount),
                            $points,
                        ),
                    ];
                })
                ->all(),
        ];
    }

    /**
     * @return array{
     *     rows: array<int, array{organization: string, plan: string, expires_at: string}>,
     *     has_more: bool,
     *     view_all_url: string
     * }
     */
    private function expiringSubscriptions(): array
    {
        $subscriptions = Subscription::query()
            ->forSuperadminControlPlane()
            ->where('status', SubscriptionStatus::ACTIVE)
            ->expiringWithin(30)
            ->limit(6)
            ->get();

        return [
            'rows' => $subscriptions
                ->take(5)
                ->map(fn (Subscription $subscription): array => [
                    'organization' => $subscription->organization?->name ?? $this->notAvailable(),
                    'plan' => $subscription->plan?->label() ?? $this->notAvailable(),
                    'expires_at' => $subscription->expires_at?->toDateString() ?? $this->notAvailable(),
                ])
                ->all(),
            'has_more' => $subscriptions->count() > 5,
            'view_all_url' => route('filament.admin.resources.subscriptions.index', [
                'tableFilters' => [
                    'expiring_soon' => [
                        'isActive' => true,
                    ],
                ],
            ]),
        ];
    }

    /**
     * @return array<int, array{type: string, ip_address: string, severity: string, occurred_ago: string}>
     */
    private function recentSecurityViolations(): array
    {
        return SecurityViolation::query()
            ->select(['id', 'type', 'severity', 'ip_address', 'occurred_at'])
            ->latest('occurred_at')
            ->limit(5)
            ->get()
            ->map(fn (SecurityViolation $violation): array => [
                'type' => $violation->type?->label() ?? $this->notAvailable(),
                'ip_address' => $violation->ip_address ?: $this->notAvailable(),
                'severity' => $violation->severity?->label() ?? $this->notAvailable(),
                'occurred_ago' => $violation->occurred_at?->locale(app()->getLocale())->diffForHumans() ?? $this->notAvailable(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    private function recentOrganizationsColumns(): array
    {
        return [
            ['key' => 'name', 'label' => __('dashboard.platform_recent_organizations.columns.name')],
            ['key' => 'owner_email', 'label' => __('dashboard.platform_recent_organizations.columns.owner_email')],
            ['key' => 'plan_type', 'label' => __('dashboard.platform_recent_organizations.columns.plan_type')],
            ['key' => 'subscription_status', 'label' => __('dashboard.platform_recent_organizations.columns.subscription_status')],
            ['key' => 'properties_count', 'label' => __('dashboard.platform_recent_organizations.columns.properties_count')],
            ['key' => 'tenants_count', 'label' => __('dashboard.platform_recent_organizations.columns.tenants_count')],
            ['key' => 'date_created', 'label' => __('dashboard.platform_recent_organizations.columns.date_created')],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function recentOrganizationsRows(int $limit = 10): array
    {
        return Organization::query()
            ->select(['id', 'name', 'owner_user_id', 'created_at'])
            ->with([
                'owner:id,email',
                'currentSubscription:id,organization_id,plan,status',
            ])
            ->withCount([
                'properties',
                'users as tenants_count' => fn (Builder $query): Builder => $query->where('role', UserRole::TENANT),
            ])
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Organization $organization): array => [
                'name' => $organization->name,
                'url' => route('filament.admin.resources.organizations.view', ['record' => $organization]),
                'owner_email' => $organization->owner?->email ?? $this->notAvailable(),
                'plan_type' => $organization->currentSubscription?->plan?->label() ?? $this->notAvailable(),
                'subscription_status' => $organization->currentSubscription?->status?->label() ?? $this->notAvailable(),
                'properties_count' => (string) ($organization->properties_count ?? 0),
                'tenants_count' => (string) ($organization->tenants_count ?? 0),
                'date_created' => $organization->created_at?->toDateString() ?? $this->notAvailable(),
            ])
            ->all();
    }

    private function notAvailable(): string
    {
        return __('dashboard.not_available');
    }

    private function formatCurrency(float $amount, string $currency = 'EUR'): string
    {
        return __('dashboard.currency_amount', [
            'currency' => $currency,
            'amount' => number_format($amount, 2, '.', ''),
        ]);
    }
}
