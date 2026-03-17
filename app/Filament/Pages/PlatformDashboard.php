<?php

namespace App\Filament\Pages;

use App\Enums\SubscriptionStatus;
use App\Models\Organization;
use App\Models\SecurityViolation;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Filament\Pages\Page;

class PlatformDashboard extends Page
{
    protected static ?string $slug = 'platform-dashboard';

    protected static ?string $navigationLabel = null;

    protected string $view = 'filament.pages.platform-dashboard';

    public function getTitle(): string
    {
        return __('dashboard.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.title');
    }

    /**
     * @return array{
     *     metrics: array<int, array{label: string, value: string}>,
     *     revenueByPlan: array<int, array{plan: string, amount: string}>,
     *     expiringSubscriptions: array<int, array{organization: string, plan: string, expires_at: string}>,
     *     recentSecurityViolations: array<int, array{organization: string, summary: string, severity: string}>,
     *     recentOrganizations: array<int, array{name: string, slug: string}>
     * }
     */
    protected function getViewData(): array
    {
        $revenue = (float) SubscriptionPayment::query()
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        $revenueByPlan = SubscriptionPayment::query()
            ->select([
                'id',
                'subscription_id',
                'amount',
                'paid_at',
            ])
            ->with([
                'subscription:id,plan',
            ])
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->get()
            ->groupBy(fn (SubscriptionPayment $payment): string => $payment->subscription?->plan?->label() ?? __('dashboard.not_available'))
            ->map(fn ($payments, string $plan): array => [
                'plan' => $plan,
                'amount' => 'EUR '.number_format((float) $payments->sum('amount'), 2),
            ])
            ->values()
            ->all();

        $expiringSubscriptions = Subscription::query()
            ->select([
                'id',
                'organization_id',
                'plan',
                'status',
                'expires_at',
            ])
            ->with([
                'organization:id,name',
            ])
            ->where('status', SubscriptionStatus::ACTIVE)
            ->whereBetween('expires_at', [now(), now()->addDays(30)])
            ->orderBy('expires_at')
            ->limit(5)
            ->get()
            ->map(fn (Subscription $subscription): array => [
                'organization' => $subscription->organization?->name ?? __('dashboard.not_available'),
                'plan' => $subscription->plan?->label() ?? __('dashboard.not_available'),
                'expires_at' => $subscription->expires_at?->toDateString() ?? __('dashboard.not_available'),
            ])
            ->all();

        $recentSecurityViolations = SecurityViolation::query()
            ->select([
                'id',
                'organization_id',
                'severity',
                'summary',
                'occurred_at',
            ])
            ->with([
                'organization:id,name',
            ])
            ->latest('occurred_at')
            ->limit(5)
            ->get()
            ->map(fn (SecurityViolation $violation): array => [
                'organization' => $violation->organization?->name ?? __('dashboard.not_available'),
                'summary' => $violation->summary,
                'severity' => ucfirst((string) ($violation->severity->value ?? $violation->severity)),
            ])
            ->all();

        $recentOrganizations = Organization::query()
            ->select([
                'id',
                'name',
                'slug',
                'created_at',
            ])
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (Organization $organization): array => [
                'name' => $organization->name,
                'slug' => $organization->slug,
            ])
            ->all();

        return [
            'metrics' => [
                [
                    'label' => __('dashboard.platform_metrics.total_organizations'),
                    'value' => (string) Organization::query()->count(),
                ],
                [
                    'label' => __('dashboard.platform_metrics.active_subscriptions'),
                    'value' => (string) Subscription::query()
                        ->where('status', SubscriptionStatus::ACTIVE)
                        ->count(),
                ],
                [
                    'label' => __('dashboard.platform_metrics.platform_revenue_this_month'),
                    'value' => 'EUR '.number_format($revenue, 2),
                ],
                [
                    'label' => __('dashboard.platform_metrics.security_violations_last_7_days'),
                    'value' => (string) SecurityViolation::query()
                        ->where('occurred_at', '>=', now()->subDays(7))
                        ->count(),
                ],
            ],
            'revenueByPlan' => $revenueByPlan,
            'expiringSubscriptions' => $expiringSubscriptions,
            'recentSecurityViolations' => $recentSecurityViolations,
            'recentOrganizations' => $recentOrganizations,
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}
