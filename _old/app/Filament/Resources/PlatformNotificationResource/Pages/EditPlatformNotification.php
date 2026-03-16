<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlatformNotificationResource\Pages;

use App\Filament\Resources\PlatformNotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlatformNotification extends EditRecord
{
    protected static string $resource = PlatformNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}