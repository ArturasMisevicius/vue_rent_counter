<?php

namespace App\Filament\Resources\Buildings\Pages;

use App\Actions\Admin\Buildings\CreateBuildingAction;
use App\Filament\Resources\Buildings\BuildingResource;
use App\Support\Admin\OrganizationContext;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBuilding extends CreateRecord
{
    protected static string $resource = BuildingResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $organization = app(OrganizationContext::class)->currentOrganization();

        abort_if($organization === null, 403);

        return app(CreateBuildingAction::class)->handle($organization, $data);
    }

    protected function getRedirectUrl(): string
    {
        return BuildingResource::getUrl('index');
    }
}
