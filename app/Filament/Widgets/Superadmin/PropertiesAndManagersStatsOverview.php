<?php

namespace App\Filament\Widgets\Superadmin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Property;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PropertiesAndManagersStatsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '60s';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalProperties = Property::query()->count();

        $occupiedProperties = Property::query()
            ->whereHas('currentAssignment')
            ->count();

        $activeManagers = User::query()
            ->where('role', UserRole::MANAGER)
            ->where('status', UserStatus::ACTIVE)
            ->count();

        $organizationsWithManagers = User::query()
            ->where('role', UserRole::MANAGER)
            ->whereNotNull('organization_id')
            ->distinct('organization_id')
            ->count('organization_id');

        return [
            Stat::make('Total Properties', (string) $totalProperties)->color('primary'),
            Stat::make('Occupied Properties', (string) $occupiedProperties)->color('success'),
            Stat::make('Active Managers', (string) $activeManagers)->color('info'),
            Stat::make('Orgs With Managers', (string) $organizationsWithManagers)->color('warning'),
        ];
    }
}
