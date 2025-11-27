<?php

declare(strict_types=1);

namespace App\Filament\Resources\MeterResource\Pages;

use App\Filament\Resources\MeterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMeter extends EditRecord
{
    protected static string $resource = MeterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label(__('meters.actions.delete'))
                ->requiresConfirmation()
                ->modalHeading(__('meters.modals.delete_heading'))
                ->modalDescription(__('meters.modals.delete_description'))
                ->modalSubmitActionLabel(__('meters.modals.delete_confirm')),
        ];
    }

    /**
     * Get the redirect URL after updating the record.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Get the success notification title.
     */
    protected function getSavedNotificationTitle(): ?string
    {
        return __('meters.notifications.updated');
    }
}
