<?php

namespace App\Filament\Resources\PlatformOrganizationInvitationResource\Actions;

use App\Models\PlatformOrganizationInvitation;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

class ResendInvitationAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'resend';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Resend Invitation');

        $this->icon('heroicon-o-paper-airplane');

        $this->color('info');

        $this->visible(fn (PlatformOrganizationInvitation $record): bool => 
            $record->status === 'pending'
        );

        $this->requiresConfirmation();

        $this->modalHeading('Resend Invitation');

        $this->modalDescription('This will generate a new token and extend the expiry date by 7 days. A new invitation email will be sent.');

        $this->modalSubmitActionLabel('Resend');

        $this->action(function (PlatformOrganizationInvitation $record) {
            $record->resend();

            // TODO: Send invitation email
            // Mail::to($record->admin_email)->send(new OrganizationInvitationMail($record));

            Notification::make()
                ->success()
                ->title('Invitation Resent')
                ->body("Invitation to {$record->organization_name} has been resent to {$record->admin_email}")
                ->send();
        });
    }
}
