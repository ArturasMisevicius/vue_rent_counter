<?php

namespace App\Filament\Resources\ServiceConfigurations\Pages;

use App\Filament\Resources\ServiceConfigurations\ServiceConfigurationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditServiceConfiguration extends EditRecord
{
    protected static string $resource = ServiceConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
