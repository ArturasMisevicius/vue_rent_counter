<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Actions\Superadmin\Organizations\CreateOrganizationAction;
use App\Filament\Resources\Organizations\OrganizationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOrganization extends CreateRecord
{
    protected static string $resource = OrganizationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateOrganizationAction::class)($data);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', [
            'record' => $this->getRecord(),
        ]);
    }
}
