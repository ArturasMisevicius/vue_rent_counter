<?php

namespace App\Filament\Resources\Tariffs\Pages;

use App\Actions\Admin\Tariffs\CreateTariffAction;
use App\Filament\Resources\Tariffs\TariffResource;
use App\Support\Admin\OrganizationContext;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTariff extends CreateRecord
{
    protected static string $resource = TariffResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $organization = app(OrganizationContext::class)->currentOrganization();

        abort_if($organization === null, 403);

        return app(CreateTariffAction::class)->handle($organization, $data);
    }

    protected function getRedirectUrl(): string
    {
        return TariffResource::getUrl('index');
    }
}
