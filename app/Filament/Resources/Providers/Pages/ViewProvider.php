<?php

namespace App\Filament\Resources\Providers\Pages;

use App\Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Providers\ProviderResource;

class ViewProvider extends ViewRecord
{
    protected static string $resource = ProviderResource::class;

    public function getTitle(): string
    {
        return __('admin.providers.view_title');
    }
}
