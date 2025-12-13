<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Resources\TenantResource\Pages;

use App\Filament\Clusters\SuperAdmin\Resources\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

final class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('superadmin.tenant.actions.create')),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('superadmin.tenant.tabs.all')),
            
            'active' => Tab::make(__('superadmin.tenant.tabs.active'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)->whereNull('suspended_at'))
                ->badge(fn () => \App\Models\Organization::where('is_active', true)->whereNull('suspended_at')->count()),
            
            'suspended' => Tab::make(__('superadmin.tenant.tabs.suspended'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('suspended_at'))
                ->badge(fn () => \App\Models\Organization::whereNotNull('suspended_at')->count()),
            
            'trial' => Tab::make(__('superadmin.tenant.tabs.trial'))
                ->modifyQueryUsing(fn (Builder $query) => $query->onTrial())
                ->badge(fn () => \App\Models\Organization::onTrial()->count()),
            
            'expired' => Tab::make(__('superadmin.tenant.tabs.expired'))
                ->modifyQueryUsing(fn (Builder $query) => $query->withExpiredSubscription())
                ->badge(fn () => \App\Models\Organization::withExpiredSubscription()->count()),
        ];
    }
}