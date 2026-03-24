<?php

namespace App\Filament\Widgets\Superadmin;

use App\Enums\SubscriptionPlan;
use App\Models\SubscriptionPayment;
use Filament\Widgets\Widget;

class RevenueByPlanChart extends Widget
{
    protected ?string $pollingInterval = '60s';

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.superadmin.revenue-by-plan-chart';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $payments = SubscriptionPayment::query()
            ->select(['id', 'subscription_id', 'amount', 'paid_at'])
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->with(['subscription:id,plan'])
            ->get();

        return [
            'totals' => collect(SubscriptionPlan::cases())
                ->map(fn (SubscriptionPlan $plan): array => [
                    'label' => $plan->label(),
                    'amount' => $this->formatCurrency((float) $payments
                        ->filter(fn (SubscriptionPayment $payment): bool => $payment->subscription?->plan === $plan)
                        ->sum('amount')),
                ])
                ->all(),
        ];
    }

    private function formatCurrency(float $amount, string $currency = 'EUR'): string
    {
        return __('dashboard.currency_amount', [
            'currency' => $currency,
            'amount' => number_format($amount, 2, '.', ''),
        ]);
    }
}
