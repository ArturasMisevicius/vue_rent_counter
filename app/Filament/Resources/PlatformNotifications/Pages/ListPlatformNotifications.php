<?php

namespace App\Filament\Resources\PlatformNotifications\Pages;

use App\Filament\Resources\PlatformNotifications\PlatformNotificationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlatformNotifications extends ListRecords
{
    protected static string $resource = PlatformNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
