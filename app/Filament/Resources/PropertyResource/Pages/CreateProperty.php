<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Resources\PropertyResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateProperty extends CreateRecord
{
    protected static string $resource = PropertyResource::class;

    /**
     * Mutate the form data before creating the record.
     * Automatically assigns tenant_id from authenticated user (Requirements 3.5).
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        
        if ($user instanceof User && $user->tenant_id) {
            $data['tenant_id'] = $user->tenant_id;
        }
        
        return $data;
    }

    /**
     * Get the success notification after creating the record.
     */
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('properties.notifications.created.title'))
            ->body(__('properties.notifications.created.body'));
    }

    /**
     * Get the redirect URL after creating the record.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

