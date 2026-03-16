<?php

namespace App\Filament\Resources\PlatformOrganizationInvitationResource\Actions;

use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class BulkResendAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'bulkResend';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Resend Invitations');

        $this->icon('heroicon-o-paper-airplane');

        $this->color('info');

        $this->requiresConfirmation();

        $this->modalHeading('Resend Selected Invitations');

        $this->modalDescription('This will generate new tokens and extend expiry dates for all selected pending invitations.');

        $this->modalSubmitActionLabel('Resend All');

        $this->deselectRecordsAfterCompletion();

        $this->action(function (Collection $records) {
            $resent = 0;
            $skipped = 0;

            foreach ($records as $record) {
                if ($record->status === 'pending') {
                    $record->resend();
                    // TODO: Send invitation email
                    // Mail::to($record->admin_email)->send(new OrganizationInvitationMail($record));
                    $resent++;
                } else {
                    $skipped++;
                }
            }

            if ($resent > 0) {
                Notification::make()
                    ->success()
                    ->title('Invitations Resent')
                    ->body("Successfully resent {$resent} invitation(s)" . ($skipped > 0 ? " ({$skipped} skipped - not pending)" : ''))
                    ->send();
            } else {
                Notification::make()
                    ->warning()
                    ->title('No Invitations Resent')
                    ->body('All selected invitations were skipped (not pending)')
                    ->send();
            }
        });
    }
}
