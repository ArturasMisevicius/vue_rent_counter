<?php

namespace App\Filament\Resources\ServiceConfigurations\Pages;

use App\Filament\Actions\Help\ContextualHelpAction;
use App\Filament\Resources\ServiceConfigurations\ServiceConfigurationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceConfigurations extends ListRecords
{
    protected static string $resource = ServiceConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ContextualHelpAction::make('service_configurations.index'),
            CreateAction::make(),
        ];
    }
}
