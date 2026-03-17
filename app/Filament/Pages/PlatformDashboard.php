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

    /**
     * @return array{metrics: array<int, array{label: string, value: string}>}
     */
    protected function getViewData(): array
    {
        $revenue = (float) SubscriptionPayment::query()
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

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
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}
