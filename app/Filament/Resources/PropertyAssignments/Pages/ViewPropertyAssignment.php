<?php

namespace App\Filament\Resources\PropertyAssignments\Pages;

use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Filament\Resources\PropertyAssignments\PropertyAssignmentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPropertyAssignment extends ViewRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = PropertyAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
