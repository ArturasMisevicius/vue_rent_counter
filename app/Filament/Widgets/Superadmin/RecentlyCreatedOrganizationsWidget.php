<?php

namespace App\Filament\Widgets\Superadmin;

use App\Models\Organization;
use Filament\Widgets\Widget;

class RecentlyCreatedOrganizationsWidget extends Widget
{
    protected ?string $pollingInterval = '60s';

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.superadmin.recently-created-organizations-widget';

    protected int|string|array $columnSpan = 1;

    protected function getViewData(): array
    {
        return [
            'organizations' => Organization::query()
                ->forSuperadminControlPlane()
                ->latest('created_at')
                ->limit(5)
                ->get(),
        ];
    }
}
