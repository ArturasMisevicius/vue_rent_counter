<?php

namespace App\Filament\Resources\PropertyAssignments\Pages;

use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Filament\Resources\PropertyAssignments\PropertyAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPropertyAssignment extends EditRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = PropertyAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
