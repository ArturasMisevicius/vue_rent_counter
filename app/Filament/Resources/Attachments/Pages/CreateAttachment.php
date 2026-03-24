<?php

namespace App\Filament\Resources\Attachments\Pages;

use App\Filament\Resources\Attachments\AttachmentResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use Filament\Resources\Pages\CreateRecord;

class CreateAttachment extends CreateRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = AttachmentResource::class;
}
