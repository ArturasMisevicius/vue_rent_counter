<?php

namespace App\Filament\Resources\FrameworkShowcases\Pages;

use App\Filament\Resources\FrameworkShowcases\FrameworkShowcaseResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFrameworkShowcase extends EditRecord
{
    protected static string $resource = FrameworkShowcaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
