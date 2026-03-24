<?php

namespace App\Filament\Resources\ServiceConfigurations\Pages;

use App\Filament\Actions\Admin\ServiceConfigurations\CreateServiceConfigurationAction;
use App\Filament\Resources\ServiceConfigurations\ServiceConfigurationResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateServiceConfiguration extends CreateRecord
{
    protected static string $resource = ServiceConfigurationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $organization = app(OrganizationContext::class)->currentOrganization();

        if ($organization === null) {
            $user = Auth::user();

            if (! $user instanceof User) {
                abort(403);
            }

            abort_if(! $user->isSuperadmin(), 403);

            $organizationId = (int) ($data['organization_id'] ?? 0);

            if ($organizationId <= 0) {
                $organizationId = (int) Property::query()
                    ->whereKey((int) ($data['property_id'] ?? 0))
                    ->value('organization_id');
            }

            abort_if($organizationId <= 0, 403);

            $organization = Organization::query()->findOrFail($organizationId);
        }

        unset($data['organization_id']);

        return app(CreateServiceConfigurationAction::class)->handle($organization, $data);
    }

    protected function getRedirectUrl(): string
    {
        return ServiceConfigurationResource::getUrl('index');
    }
}
