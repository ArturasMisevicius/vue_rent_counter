<?php

namespace App\Filament\Resources\Buildings\Pages;

use App\Filament\Resources\Buildings\BuildingResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Tabs\Tab;

class ViewBuilding extends ViewRecord
{
    protected static string $resource = BuildingResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getBreadcrumbs(): array
    {
        return [
            BuildingResource::getUrl('index') => BuildingResource::getPluralModelLabel(),
            $this->record->name,
        ];
    }

    public function getTitle(): string
    {
        return __('admin.buildings.view_title');
    }

    public function getContentTabLabel(): ?string
    {
        return __('admin.buildings.tabs.overview');
    }

    public function getContentTabComponent(): Tab
    {
        $tab = parent::getContentTabComponent();
        $meterCount = $this->record->getAttribute('meters_count');

        return $meterCount === null
            ? $tab
            : $tab->badge((string) $meterCount);
    }
}
