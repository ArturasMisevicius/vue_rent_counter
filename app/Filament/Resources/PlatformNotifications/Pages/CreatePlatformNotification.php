<?php

namespace App\Filament\Resources\PlatformNotifications\Pages;

use App\Enums\PlatformNotificationSeverity;
use App\Filament\Actions\Superadmin\Notifications\SavePlatformNotificationDraftAction;
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
            'target_mode' => $data['target_mode'] ?? 'all',
            'organization_ids' => $data['organization_ids'] ?? [],
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return PlatformNotificationResource::getUrl('index');
    }
}
