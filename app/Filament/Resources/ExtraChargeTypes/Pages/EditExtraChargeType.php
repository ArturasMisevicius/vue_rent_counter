<?php

namespace App\Filament\Resources\ExtraChargeTypes\Pages;

use App\Filament\Resources\ExtraChargeTypes\ExtraChargeTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditExtraChargeType extends EditRecord
{
    protected static string $resource = ExtraChargeTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
