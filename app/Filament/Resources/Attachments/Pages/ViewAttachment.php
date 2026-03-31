<?php

namespace App\Filament\Resources\Attachments\Pages;

use App\Filament\Resources\Attachments\AttachmentResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;

class ViewAttachment extends ViewRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = AttachmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
