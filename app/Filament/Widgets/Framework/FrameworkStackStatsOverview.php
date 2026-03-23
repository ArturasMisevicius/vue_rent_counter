<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Framework;

use App\Models\FrameworkShowcase;
use App\Models\Organization;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

final class FrameworkStackStatsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            Stat::make('Organizations', (string) Organization::query()->count())
                ->description('Available platform tenants')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary')
                ->chart($this->dailyCounts(Organization::class)),
            Stat::make('Users', (string) User::query()->count())
                ->description('Accounts visible to the control plane')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart($this->dailyCounts(User::class)),
            Stat::make('Showcases', (string) FrameworkShowcase::query()->count())
                ->description('Demo resource records')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('warning')
                ->chart($this->dailyCounts(FrameworkShowcase::class)),
        ];
    }

    /**
     * @param  class-string<Model>  $model
     * @return array<int, int>
     */
    private function dailyCounts(string $model): array
    {
        return collect(range(6, 0))
            ->map(fn (int $offset): int => $model::query()
                ->whereDate('created_at', now()->subDays($offset)->toDateString())
                ->count())
            ->all();
    }
}
