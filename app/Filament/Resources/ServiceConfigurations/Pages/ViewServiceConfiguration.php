<?php

namespace App\Filament\Resources\ServiceConfigurations\Pages;

use App\Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\ServiceConfigurations\ServiceConfigurationResource;
use Filament\Actions\Action;

class ViewServiceConfiguration extends ViewRecord
{
    protected static string $resource = ServiceConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editServiceConfiguration')
                ->label(__('admin.actions.edit'))
                ->url(fn (): string => ServiceConfigurationResource::getUrl('edit', [
                    'record' => $this->record,
                ]))
                ->button(),
        ];
    }
}
