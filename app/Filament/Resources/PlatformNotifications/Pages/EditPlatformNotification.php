<?php

namespace App\Filament\Resources\PlatformNotifications\Pages;

use App\Actions\Superadmin\Notifications\SavePlatformNotificationDraftAction;
use App\Filament\Resources\PlatformNotifications\PlatformNotificationResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPlatformNotification extends EditRecord
{
    protected static string $resource = PlatformNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(SavePlatformNotificationDraftAction::class)($data, $record);
    }
}
