<?php

namespace App\Filament\Resources\PlatformOrganizationInvitationResource\Pages;

use App\Filament\Resources\PlatformOrganizationInvitationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlatformOrganizationInvitations extends ListRecords
{
    protected static string $resource = PlatformOrganizationInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
