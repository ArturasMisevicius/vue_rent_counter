<?php

namespace App\Filament\Resources\TaskAssignments\Pages;

use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\TaskAssignments\TaskAssignmentResource;
use Filament\Actions\EditAction;

class ViewTaskAssignment extends ViewRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = TaskAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
