<?php

namespace App\Filament\Resources\ExtraCharges\Pages;

use App\Filament\Resources\ExtraCharges\ExtraChargeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewExtraCharge extends ViewRecord
{
    protected static string $resource = ExtraChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
