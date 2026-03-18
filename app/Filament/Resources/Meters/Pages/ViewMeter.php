<?php

namespace App\Filament\Resources\Meters\Pages;

use App\Filament\Resources\Meters\MeterResource;
use App\Filament\Resources\Meters\Widgets\MeterConsumptionChart;
use Filament\Resources\Pages\ViewRecord;

class ViewMeter extends ViewRecord
{
    protected static string $resource = MeterResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

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

    public function getContentTabLabel(): ?string
    {
        return __('admin.meters.sections.details');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MeterConsumptionChart::make([
                'meterId' => (int) $this->record->getKey(),
            ]),
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
