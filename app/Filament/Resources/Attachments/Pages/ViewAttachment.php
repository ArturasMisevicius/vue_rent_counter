<?php

namespace App\Filament\Resources\Attachments\Pages;

use App\Filament\Resources\Attachments\AttachmentResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

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
