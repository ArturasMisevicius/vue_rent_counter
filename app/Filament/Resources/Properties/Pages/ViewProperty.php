<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Filament\Resources\Properties\PropertyResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProperty extends ViewRecord
{
    protected static string $resource = PropertyResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            PropertyResource::getUrl('index') => PropertyResource::getPluralModelLabel(),
            $this->record->name,
        ];
    }

    public function getTitle(): string
    {
        return __('admin.properties.view_title');
    }

    protected function getHeaderActions(): array
    {
        if (PropertyResource::shouldInterceptGraceEditAction()) {
            return [
                PropertyResource::makeSubscriptionInfoAction(
                    name: 'edit',
                    resource: 'properties',
                    label: __('filament-actions::edit.single.label', [
                        'label' => PropertyResource::getModelLabel(),
                    ]),
                ),
            ];
        }

        if (! PropertyResource::canEdit($this->record)) {
            return [];
        }

        return [
            EditAction::make(),
        ];
    }
}
