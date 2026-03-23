<?php

namespace App\Filament\Resources\FrameworkShowcases\Pages;

use App\Filament\Resources\FrameworkShowcases\FrameworkShowcaseResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFrameworkShowcase extends ViewRecord
{
    protected static string $resource = FrameworkShowcaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
