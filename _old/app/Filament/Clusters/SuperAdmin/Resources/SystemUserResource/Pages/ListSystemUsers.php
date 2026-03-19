<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Resources\SystemUserResource\Pages;

use App\Filament\Clusters\SuperAdmin\Resources\SystemUserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

final class ListSystemUsers extends ListRecords
{
    protected static string $resource = SystemUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('superadmin.user.actions.create')),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('superadmin.user.tabs.all')),

            'active' => Tab::make(__('superadmin.user.tabs.active'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(fn () => User::where('is_active', true)->count()),

            'suspended' => Tab::make(__('superadmin.user.tabs.suspended'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('suspended_at'))
                ->badge(fn () => User::whereNotNull('suspended_at')->count()),

            'unverified' => Tab::make(__('superadmin.user.tabs.unverified'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('email_verified_at'))
                ->badge(fn () => User::whereNull('email_verified_at')->count()),

            'recent' => Tab::make(__('superadmin.user.tabs.recent'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('last_login_at', '>', now()->subDays(30)))
                ->badge(fn () => User::where('last_login_at', '>', now()->subDays(30))->count()),
        ];
    }
}
