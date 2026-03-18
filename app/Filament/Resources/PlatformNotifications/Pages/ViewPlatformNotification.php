<?php

namespace App\Filament\Resources\PlatformNotifications\Pages;

use App\Filament\Resources\PlatformNotifications\PlatformNotificationResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPlatformNotification extends ViewRecord
{
    protected static string $resource = PlatformNotificationResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getBreadcrumbs(): array
    {
        return [
            PlatformNotificationResource::getUrl('index') => PlatformNotificationResource::getPluralModelLabel(),
            $this->record->title,
        ];
    }

    public function getTitle(): string
    {
        return 'Platform Notification';
    }

    public function getContentTabLabel(): ?string
    {
        return 'Overview';
    }
}
