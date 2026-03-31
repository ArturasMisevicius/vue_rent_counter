<?php

namespace App\Filament\Resources\Tariffs\Pages;

use App\Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Tariffs\TariffResource;

class ViewTariff extends ViewRecord
{
    protected static string $resource = TariffResource::class;

    public function getTitle(): string
    {
        return __('admin.tariffs.view_title');
    }
}
