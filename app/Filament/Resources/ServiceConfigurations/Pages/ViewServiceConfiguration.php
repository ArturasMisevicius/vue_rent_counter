<?php

namespace App\Filament\Resources\ServiceConfigurations\Pages;

use App\Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\ServiceConfigurations\ServiceConfigurationResource;
use Filament\Actions\EditAction;

class ViewServiceConfiguration extends ViewRecord
{
    protected static string $resource = ServiceConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
