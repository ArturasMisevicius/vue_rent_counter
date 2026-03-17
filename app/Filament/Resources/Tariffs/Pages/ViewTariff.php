<?php

namespace App\Filament\Resources\Tariffs\Pages;

use App\Filament\Resources\Tariffs\TariffResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTariff extends ViewRecord
{
    protected static string $resource = TariffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
