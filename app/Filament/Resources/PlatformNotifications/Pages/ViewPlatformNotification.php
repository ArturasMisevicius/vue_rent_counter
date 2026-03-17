<?php

namespace App\Filament\Resources\PlatformNotifications\Pages;

use App\Filament\Resources\PlatformNotifications\PlatformNotificationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPlatformNotification extends ViewRecord
{
    protected static string $resource = PlatformNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => $this->getRecord()->canBeSent()),
        ];
    }
}
