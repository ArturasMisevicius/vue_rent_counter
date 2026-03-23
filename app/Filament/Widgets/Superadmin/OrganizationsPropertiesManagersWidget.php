<?php

namespace App\Filament\Widgets\Superadmin;

use App\Enums\UserRole;
use App\Models\Organization;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class OrganizationsPropertiesManagersWidget extends Widget
{
    protected ?string $pollingInterval = '60s';

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.superadmin.organizations-properties-managers-widget';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'organizations' => Organization::query()
                ->forSuperadminControlPlane()
                ->withCount([
                    'users as managers_count' => fn (Builder $query): Builder => $query->where('role', UserRole::MANAGER),
                ])
                ->orderByDesc('properties_count')
                ->limit(8)
                ->get(),
        ];
    }
}
