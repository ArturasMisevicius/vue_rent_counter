<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Filament\Actions\Admin\Properties\CreatePropertyAction;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Support\Admin\OrganizationContext;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProperty extends CreateRecord
{
    protected static string $resource = PropertyResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $organization = app(OrganizationContext::class)->currentOrganization();

        abort_if($organization === null, 403);

        return app(CreatePropertyAction::class)->handle($organization, $data);
    }

    protected function getRedirectUrl(): string
    {
        return PropertyResource::getUrl('index');
    }
}
