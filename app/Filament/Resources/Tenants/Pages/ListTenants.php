<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Resources\Tenants\TenantResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(TenantResource::canViewAny(), 403);
    }

    protected function getHeaderActions(): array
    {
        if (TenantResource::shouldShowBlockedCreateAction('tenants')) {
            return [
                TenantResource::makeSubscriptionInfoAction(
                    name: 'create',
                    resource: 'tenants',
                    label: __('admin.tenants.actions.new_tenant'),
                ),
            ];
        }

        if (! TenantResource::canCreate()) {
            return [];
        }

        return [
            Action::make('create')
                ->label(__('admin.tenants.actions.new_tenant'))
                ->url(TenantResource::getUrl('create'))
                ->icon('heroicon-m-plus')
                ->button(),
        ];
    }
}
