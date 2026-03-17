<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Actions\Admin\Tenants\CreateTenantAction;
use App\Filament\Resources\Tenants\TenantResource;
use App\Support\Admin\OrganizationContext;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $actor = app(OrganizationContext::class)->currentUser();

        abort_if($actor === null, 403);

        return app(CreateTenantAction::class)->handle($actor, $data);
    }

    protected function getRedirectUrl(): string
    {
        return TenantResource::getUrl('index');
    }
}
