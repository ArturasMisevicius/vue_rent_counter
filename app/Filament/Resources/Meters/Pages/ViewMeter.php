<?php

namespace App\Filament\Resources\Meters\Pages;

use App\Filament\Resources\Meters\MeterResource;
use App\Filament\Resources\Meters\Widgets\MeterConsumptionChart;
use App\Filament\Resources\Pages\Concerns\HasDeferredRelationManagerTabBadges;
use App\Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;

class ViewMeter extends ViewRecord
{
    use HasDeferredRelationManagerTabBadges;

    protected static string $resource = MeterResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getBreadcrumbs(): array
    {
        return [
            MeterResource::getUrl('index') => MeterResource::getPluralModelLabel(),
            $this->record->displayName(),
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

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label(__('admin.actions.edit')),
        ];
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
