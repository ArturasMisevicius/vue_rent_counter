<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Filament\Resources\Tasks\TaskResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTask extends ViewRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
