<?php

namespace App\Filament\Resources\PlatformOrganizationInvitationResource\Pages;

use App\Filament\Resources\PlatformOrganizationInvitationResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePlatformOrganizationInvitation extends CreateRecord
{
    protected static string $resource = PlatformOrganizationInvitationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['invited_by'] = auth()->id();
        $data['status'] = 'pending';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
