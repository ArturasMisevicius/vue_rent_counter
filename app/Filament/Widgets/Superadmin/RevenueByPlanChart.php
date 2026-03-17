<?php

namespace App\Filament\Widgets\Superadmin;

use App\Enums\SubscriptionPlan;
use App\Models\Subscription;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

class RevenueByPlanChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = '60s';

    protected ?string $heading = 'Revenue By Plan';

    protected ?string $description = 'Monthly totals for Basic, Professional, and Enterprise plans.';

    protected function getData(): array
    {
        $monthStart = now()->startOfMonth();

        $paymentsByPlan = Subscription::query()
            ->select([
                'id',
                'plan_name_snapshot',
            ])
            ->withSum([
                'payments as monthly_paid_amount' => fn (Builder $query): Builder => $query
                    ->where('status', 'paid')
                    ->whereBetween('paid_at', [$monthStart, now()]),
            ], 'amount')
            ->get()
            ->groupBy(fn (Subscription $subscription): string => $subscription->plan_name_snapshot)
            ->map(fn ($subscriptions): float => round(
                $subscriptions->sum(fn (Subscription $subscription): int => (int) ($subscription->monthly_paid_amount ?? 0)) / 100,
                2,
            ));

        $labels = collect(SubscriptionPlan::cases())
            ->map(fn (SubscriptionPlan $plan): string => $plan->label())
            ->values();
        $values = $labels
            ->map(fn (string $label): float => (float) ($paymentsByPlan->get($label) ?? 0))
            ->values();

        return [
            'datasets' => [
                [
                    'label' => 'Monthly Revenue (EUR)',
                    'data' => $values->all(),
                    'backgroundColor' => [
                        '#f59e0b',
                        '#d97706',
                        '#92400e',
                    ],
                    'borderRadius' => 12,
                ],
            ],
            'labels' => $labels->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array|RawJs|null
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
