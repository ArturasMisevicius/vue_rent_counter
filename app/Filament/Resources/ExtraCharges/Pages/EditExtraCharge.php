<?php

namespace App\Filament\Resources\ExtraCharges\Pages;

use App\Filament\Resources\ExtraCharges\ExtraChargeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditExtraCharge extends EditRecord
{
    protected static string $resource = ExtraChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
