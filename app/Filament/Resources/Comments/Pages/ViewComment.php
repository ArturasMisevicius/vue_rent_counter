<?php

namespace App\Filament\Resources\Comments\Pages;

use App\Filament\Resources\Comments\CommentResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewComment extends ViewRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = CommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
