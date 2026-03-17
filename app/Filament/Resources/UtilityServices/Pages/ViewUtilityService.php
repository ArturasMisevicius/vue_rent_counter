<?php

namespace App\Filament\Resources\UtilityServices\Pages;

use App\Filament\Resources\UtilityServices\UtilityServiceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUtilityService extends ViewRecord
{
    protected static string $resource = UtilityServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
