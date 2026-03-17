<?php

namespace App\Filament\Widgets\Superadmin;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Filament\Widgets\Widget;

class ExpiringSubscriptionsWidget extends Widget
{
    protected ?string $pollingInterval = '60s';

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.superadmin.expiring-subscriptions-widget';

    protected int|string|array $columnSpan = 1;

    protected function getViewData(): array
    {
        return [
            'subscriptions' => Subscription::query()
                ->forSuperadminControlPlane()
                ->where('status', SubscriptionStatus::ACTIVE)
                ->whereBetween('expires_at', [now(), now()->addDays(30)])
                ->orderBy('expires_at')
                ->limit(5)
                ->get(),
        ];
    }
}
