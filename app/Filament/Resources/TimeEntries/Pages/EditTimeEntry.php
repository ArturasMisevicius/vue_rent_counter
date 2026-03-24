<?php

namespace App\Filament\Resources\TimeEntries\Pages;

use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Filament\Resources\TimeEntries\TimeEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTimeEntry extends EditRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = TimeEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
