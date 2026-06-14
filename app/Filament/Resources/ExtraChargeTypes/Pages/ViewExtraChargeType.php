<?php

namespace App\Filament\Resources\ExtraChargeTypes\Pages;

use App\Filament\Resources\ExtraChargeTypes\ExtraChargeTypeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewExtraChargeType extends ViewRecord
{
    protected static string $resource = ExtraChargeTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
