<?php

namespace App\Filament\Resources\OrganizationInvitationResource\Pages;

use App\Filament\Resources\OrganizationInvitationResource;
use App\Models\OrganizationInvitation;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOrganizationInvitation extends ViewRecord
{
    protected static string $resource = OrganizationInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('resend')
                ->label('Resend')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->action(function (OrganizationInvitation $record): void {
                    $record->resend();
                })
                ->visible(fn (OrganizationInvitation $record): bool => $record->isPending())
                ->successNotificationTitle('Invitation resent'),
        ];
    }
}

