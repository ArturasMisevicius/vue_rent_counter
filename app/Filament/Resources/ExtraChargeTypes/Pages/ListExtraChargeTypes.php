<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExtraChargeTypes\Pages;

use App\Filament\Actions\Help\ContextualHelpAction;
use App\Filament\Resources\ExtraChargeTypes\ExtraChargeTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExtraChargeTypes extends ListRecords
{
    protected static string $resource = ExtraChargeTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ContextualHelpAction::make('extra_charges.index'),
            CreateAction::make(),
        ];
    }
}
