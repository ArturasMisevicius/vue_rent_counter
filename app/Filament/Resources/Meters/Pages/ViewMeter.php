<?php

namespace App\Filament\Resources\Meters\Pages;

use App\Filament\Resources\Meters\MeterResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMeter extends ViewRecord
{
    protected static string $resource = MeterResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            MeterResource::getUrl('index') => MeterResource::getPluralModelLabel(),
            $this->record->name,
        ];
    }

    public function getTitle(): string
    {
        return __('admin.meters.view_title');
    }
}
