<?php

namespace App\Filament\Resources\PlatformNotifications\Pages;

use App\Filament\Resources\PlatformNotifications\PlatformNotificationResource;
use Filament\Resources\Pages\EditRecord;

class EditPlatformNotification extends EditRecord
{
    protected static string $resource = PlatformNotificationResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['target_mode'], $data['organization_ids']);

        return $data;
    }
}
