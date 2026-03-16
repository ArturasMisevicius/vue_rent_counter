<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlatformNotificationResource\Pages;

use App\Filament\Actions\SendPlatformNotificationAction;
use App\Filament\Resources\PlatformNotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlatformNotifications extends ListRecords
{
    protected static string $resource = PlatformNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SendPlatformNotificationAction::make(),
            Actions\CreateAction::make(),
        ];
    }
}