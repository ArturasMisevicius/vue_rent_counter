<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Resources\Tenants\TenantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        if (TenantResource::shouldShowBlockedCreateAction('tenants')) {
            return [
                TenantResource::makeSubscriptionInfoAction(
                    name: 'create',
                    resource: 'tenants',
                    label: __('filament-actions::create.single.label', [
                        'label' => TenantResource::getModelLabel(),
                    ]),
                ),
            ];
        }

        if (! TenantResource::canCreate()) {
            return [];
        }

        return [
            CreateAction::make(),
        ];
    }
}
