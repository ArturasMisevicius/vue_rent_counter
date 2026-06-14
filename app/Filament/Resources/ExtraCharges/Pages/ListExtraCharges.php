<?php

namespace App\Filament\Resources\ExtraCharges\Pages;

use App\Filament\Resources\ExtraCharges\ExtraChargeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExtraCharges extends ListRecords
{
    protected static string $resource = ExtraChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
