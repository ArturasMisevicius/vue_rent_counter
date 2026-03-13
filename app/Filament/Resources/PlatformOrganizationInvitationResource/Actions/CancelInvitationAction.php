<?php

namespace App\Filament\Resources\PlatformOrganizationInvitationResource\Actions;

use App\Models\PlatformOrganizationInvitation;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class CancelInvitationAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'cancel';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Cancel Invitation');

        $this->icon('heroicon-o-x-circle');

        $this->color('danger');

        $this->visible(fn (PlatformOrganizationInvitation $record): bool => 
            $record->status === 'pending'
        );

        $this->requiresConfirmation();

        $this->modalHeading('Cancel Invitation');

        $this->modalDescription('Are you sure you want to cancel this invitation? This action cannot be undone.');

        $this->modalSubmitActionLabel('Cancel Invitation');

        $this->action(function (PlatformOrganizationInvitation $record) {
            $record->cancel();

            Notification::make()
                ->success()
                ->title('Invitation Cancelled')
                ->body("Invitation to {$record->organization_name} has been cancelled")
                ->send();
        });
    }
}
