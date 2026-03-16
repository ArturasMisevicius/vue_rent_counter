<?php

namespace App\Filament\Resources\PlatformOrganizationInvitationResource\Actions;

use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class BulkDeleteExpiredAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'bulkDeleteExpired';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Delete Expired');

        $this->icon('heroicon-o-trash');

        $this->color('danger');

        $this->requiresConfirmation();

        $this->modalHeading('Delete Expired Invitations');

        $this->modalDescription('This will permanently delete all selected expired invitations. This action cannot be undone.');

        $this->modalSubmitActionLabel('Delete Expired');

        $this->deselectRecordsAfterCompletion();

        $this->action(function (Collection $records) {
            $deleted = 0;
            $skipped = 0;

            foreach ($records as $record) {
                if ($record->isExpired() || $record->status === 'cancelled') {
                    $record->delete();
                    $deleted++;
                } else {
                    $skipped++;
                }
            }

            if ($deleted > 0) {
                Notification::make()
                    ->success()
                    ->title('Invitations Deleted')
                    ->body("Successfully deleted {$deleted} invitation(s)" . ($skipped > 0 ? " ({$skipped} skipped - not expired/cancelled)" : ''))
                    ->send();
            } else {
                Notification::make()
                    ->warning()
                    ->title('No Invitations Deleted')
                    ->body('All selected invitations were skipped (not expired or cancelled)')
                    ->send();
            }
        });
    }
}
