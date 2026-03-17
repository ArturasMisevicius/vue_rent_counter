<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Actions\Admin\Tenants\DeleteTenantAction;
use App\Actions\Admin\Tenants\UpdateTenantAction;
use App\Filament\Resources\Tenants\TenantResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

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

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn (): bool => TenantResource::canDelete($this->record))
                ->using(fn (User $record) => app(DeleteTenantAction::class)->handle($record)),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return TenantResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }
}
