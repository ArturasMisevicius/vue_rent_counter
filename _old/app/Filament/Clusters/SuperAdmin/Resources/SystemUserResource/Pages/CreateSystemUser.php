<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Resources\SystemUserResource\Pages;

use App\Filament\Clusters\SuperAdmin\Resources\SystemUserResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;

final class CreateSystemUser extends CreateRecord
{
    protected static string $resource = SystemUserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate a temporary password
        $data['password'] = Hash::make('TempPassword123!');
        $data['password_reset_required'] = true;
        
        return $data;
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title(__('superadmin.user.notifications.created'))
            ->body(__('superadmin.user.notifications.created_body', ['name' => $this->getRecord()->name]))
            ->success()
            ->send();

        // TODO: Send welcome email with password reset link
    }
}