<?php

namespace App\Filament\Resources\PlatformOrganizationInvitationResource\Pages;

use App\Filament\Resources\PlatformOrganizationInvitationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlatformOrganizationInvitation extends EditRecord
{
    protected static string $resource = PlatformOrganizationInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
