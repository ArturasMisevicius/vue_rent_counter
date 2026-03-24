<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Filament\Resources\Properties\PropertyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProperties extends ListRecords
{
    protected static string $resource = PropertyResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(PropertyResource::canViewAny(), 403);
    }

    protected function getHeaderActions(): array
    {
        if (PropertyResource::shouldShowBlockedCreateAction('properties')) {
            return [
                PropertyResource::makeSubscriptionInfoAction(
                    name: 'create',
                    resource: 'properties',
                    label: __('admin.properties.actions.new_property'),
                ),
            ];
        }

        if (! PropertyResource::canCreate()) {
            return [];
        }

        return [
            CreateAction::make()
                ->label(__('admin.properties.actions.new_property')),
        ];
    }
}
