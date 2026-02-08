<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlatformNotificationResource\Pages;

use App\Filament\Resources\PlatformNotificationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePlatformNotification extends CreateRecord
{
    protected static string $resource = PlatformNotificationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        
        return $data;
    }
}