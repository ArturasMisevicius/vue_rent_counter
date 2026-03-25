<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserKycProfiles\Pages;

use App\Filament\Resources\UserKycProfiles\Pages\Concerns\InteractsWithKycAttachmentFormData;
use App\Filament\Resources\UserKycProfiles\UserKycProfileResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUserKycProfile extends EditRecord
{
    use InteractsWithKycAttachmentFormData;

    protected static string $resource = UserKycProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [
            ...$data,
            ...$this->attachmentFormDataForRecord($this->getRecord()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        [$data, $this->attachmentFormData] = $this->extractAttachmentFormData($data);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->syncKycAttachments($this->getRecord());
    }
}
