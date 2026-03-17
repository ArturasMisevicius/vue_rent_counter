<?php

namespace App\Filament\Resources\PlatformNotifications\Pages;

use App\Actions\Superadmin\Notifications\SavePlatformNotificationDraftAction;
use App\Enums\PlatformNotificationSeverity;
use App\Filament\Resources\PlatformNotifications\PlatformNotificationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePlatformNotification extends CreateRecord
{
    protected static string $resource = PlatformNotificationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(SavePlatformNotificationDraftAction::class)->handle([
            'title' => $data['title'],
            'body' => $data['body'],
            'severity' => PlatformNotificationSeverity::from($data['severity']),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return PlatformNotificationResource::getUrl('index');
    }
}
