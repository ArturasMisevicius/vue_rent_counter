<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Filament\Actions\Admin\Properties\UpdatePropertyAction;
use App\Filament\Resources\Properties\PropertyResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProperty extends EditRecord
{
    protected static string $resource = PropertyResource::class;

    public function getTitle(): string
    {
        return __('admin.properties.titles.edit', [
            'name' => $this->record->name,
        ]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdatePropertyAction::class)->handle($record, $data);
    }

    protected function getRedirectUrl(): string
    {
        return PropertyResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label(__('admin.actions.save_changes'));
    }
}
