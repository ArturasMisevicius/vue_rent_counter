<?php

declare(strict_types=1);

namespace App\Filament\Resources\MeterResource\Pages;

use App\Filament\Resources\MeterResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMeter extends CreateRecord
{
    protected static string $resource = MeterResource::class;

    /**
     * Automatically set tenant_id from authenticated user.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = auth()->user()->tenant_id;

        return $data;
    }

    /**
     * Get the redirect URL after creating the record.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Get the success notification title.
     */
    protected function getCreatedNotificationTitle(): ?string
    {
        return __('meters.notifications.created');
    }
}
