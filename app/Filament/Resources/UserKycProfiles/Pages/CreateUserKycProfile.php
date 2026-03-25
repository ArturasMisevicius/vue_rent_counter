<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserKycProfiles\Pages;

use App\Filament\Resources\UserKycProfiles\Pages\Concerns\InteractsWithKycAttachmentFormData;
use App\Filament\Resources\UserKycProfiles\UserKycProfileResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserKycProfile extends CreateRecord
{
    use InteractsWithKycAttachmentFormData;

    protected static string $resource = UserKycProfileResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        [$data, $this->attachmentFormData] = $this->extractAttachmentFormData($data);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncKycAttachments($this->getRecord());
    }
}
