<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Resources\TenantResource\Pages;

use App\Filament\Clusters\SuperAdmin\Resources\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

final class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('superadmin.tenant.notifications.updated'))
            ->body(__('superadmin.tenant.notifications.updated_body', ['name' => $this->getRecord()->name]));
    }
}