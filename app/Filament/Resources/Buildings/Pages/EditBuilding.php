<?php

namespace App\Filament\Resources\Buildings\Pages;

use App\Filament\Actions\Admin\Buildings\UpdateBuildingAction;
use App\Filament\Resources\Buildings\BuildingResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBuilding extends EditRecord
{
    protected static string $resource = BuildingResource::class;

    protected static string|array $routeMiddleware = 'manager.permission:buildings,edit';

    public function getTitle(): string
    {
        return __('admin.buildings.titles.edit', [
            'name' => $this->record->name,
        ]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateBuildingAction::class)->handle($record, $data);
    }

    protected function getRedirectUrl(): string
    {
        return BuildingResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label(__('admin.actions.save_changes'));
    }
}
