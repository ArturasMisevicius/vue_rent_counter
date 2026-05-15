<?php

namespace App\Filament\Resources\Buildings\Pages;

use App\Filament\Actions\Admin\Buildings\DeleteBuildingAction;
use App\Filament\Resources\Buildings\BuildingResource;
use App\Filament\Resources\Pages\Concerns\HasDeferredRelationManagerTabBadges;
use App\Filament\Resources\Pages\ViewRecord;
use App\Models\Building;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewBuilding extends ViewRecord
{
    use HasDeferredRelationManagerTabBadges;

    protected static string $resource = BuildingResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getBreadcrumbs(): array
    {
        return [
            BuildingResource::getUrl('index') => BuildingResource::getPluralModelLabel(),
            $this->record->displayName(),
        ];
    }

    public function getTitle(): string
    {
        return $this->record->displayName();
    }

    public function getSubheading(): ?string
    {
        return $this->record->address;
    }

    public function getContentTabLabel(): ?string
    {
        return __('admin.buildings.tabs.overview');
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label(__('admin.actions.edit')),
            DeleteAction::make()
                ->label(__('admin.actions.delete'))
                ->using(fn (Building $record) => app(DeleteBuildingAction::class)->handle($record))
                ->disabled(fn (Building $record): bool => ! $record->canBeDeletedFromAdminWorkspace())
                ->tooltip(fn (Building $record): ?string => $record->adminDeletionBlockedReason()),
        ];
    }
}
