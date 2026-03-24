<?php

namespace App\Filament\Resources\Attachments\Pages;

use App\Filament\Resources\Attachments\AttachmentResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAttachment extends EditRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = AttachmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
