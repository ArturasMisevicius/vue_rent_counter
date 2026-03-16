<?php

namespace App\Filament\Resources\PlatformOrganizationInvitationResource\Actions;

use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class BulkCancelAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'bulkCancel';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Cancel Invitations');

        $this->icon('heroicon-o-x-circle');

        $this->color('danger');

        $this->requiresConfirmation();

        $this->modalHeading('Cancel Selected Invitations');

        $this->modalDescription('Are you sure you want to cancel all selected invitations? This action cannot be undone.');

        $this->modalSubmitActionLabel('Cancel All');

        $this->deselectRecordsAfterCompletion();

        $this->action(function (Collection $records) {
            $cancelled = 0;
            $skipped = 0;

            foreach ($records as $record) {
                if ($record->status === 'pending') {
                    $record->cancel();
                    $cancelled++;
                } else {
                    $skipped++;
                }
            }

            if ($cancelled > 0) {
                Notification::make()
                    ->success()
                    ->title('Invitations Cancelled')
                    ->body("Successfully cancelled {$cancelled} invitation(s)" . ($skipped > 0 ? " ({$skipped} skipped - not pending)" : ''))
                    ->send();
            } else {
                Notification::make()
                    ->warning()
                    ->title('No Invitations Cancelled')
                    ->body('All selected invitations were skipped (not pending)')
                    ->send();
            }
        });
    }
}
