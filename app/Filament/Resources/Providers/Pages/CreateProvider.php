<?php

namespace App\Filament\Resources\Providers\Pages;

use App\Filament\Actions\Admin\Providers\CreateProviderAction;
use App\Filament\Resources\Providers\ProviderResource;
use App\Filament\Support\Admin\OrganizationContext;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProvider extends CreateRecord
{
    protected static string $resource = ProviderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $organization = app(OrganizationContext::class)->currentOrganization();

        abort_if($organization === null, 403);

        return app(CreateProviderAction::class)->handle($organization, $data);
    }

    protected function getRedirectUrl(): string
    {
        return ProviderResource::getUrl('index');
    }
}
