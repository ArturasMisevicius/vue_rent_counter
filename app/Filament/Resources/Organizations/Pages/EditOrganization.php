<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Actions\Superadmin\Organizations\UpdateOrganizationAction;
use App\Filament\Resources\Organizations\OrganizationResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditOrganization extends EditRecord
{
    protected static string $resource = OrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateOrganizationAction::class)($record, $data);
    }
}
