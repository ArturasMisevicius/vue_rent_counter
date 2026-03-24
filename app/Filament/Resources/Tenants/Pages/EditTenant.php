<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Actions\Admin\Tenants\UpdateTenantAction;
use App\Filament\Resources\Tenants\TenantResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    public function getTitle(): string
    {
        return __('admin.tenants.titles.edit', [
            'name' => $this->record->name,
        ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $record */
        $record = $this->record;

        return [
            ...$data,
            'property_id' => $record->currentPropertyAssignment?->property_id,
            'unit_area_sqm' => $record->currentPropertyAssignment?->unit_area_sqm,
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateTenantAction::class)->handle($record, $data);
    }

    protected function getRedirectUrl(): string
    {
        return TenantResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label(__('admin.actions.save_changes'));
    }
}
