<?php

namespace App\Filament\Resources\PlatformOrganizationInvitationResource\Pages;

use App\Filament\Resources\PlatformOrganizationInvitationResource;
use App\Filament\Resources\PlatformOrganizationInvitationResource\Actions\CancelInvitationAction;
use App\Filament\Resources\PlatformOrganizationInvitationResource\Actions\ResendInvitationAction;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPlatformOrganizationInvitation extends ViewRecord
{
    protected static string $resource = PlatformOrganizationInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => $this->record->status === 'pending'),
            ResendInvitationAction::make()
                ->record($this->record),
            CancelInvitationAction::make()
                ->record($this->record),
        ];
    }
}
