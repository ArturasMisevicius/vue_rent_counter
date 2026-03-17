<?php

namespace App\Filament\Widgets\Superadmin;

use App\Models\SecurityViolation;
use Filament\Widgets\Widget;

class RecentSecurityViolationsWidget extends Widget
{
    protected ?string $pollingInterval = '60s';

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.superadmin.recent-security-violations-widget';

    protected int|string|array $columnSpan = 1;

    protected function getViewData(): array
    {
        return [
            'violations' => SecurityViolation::query()
                ->select(['id', 'organization_id', 'summary', 'severity', 'ip_address', 'occurred_at'])
                ->with(['organization:id,name'])
                ->latest('occurred_at')
                ->limit(5)
                ->get(),
        ];
    }
}
