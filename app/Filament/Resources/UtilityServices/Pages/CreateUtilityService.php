<?php

namespace App\Filament\Resources\UtilityServices\Pages;

use App\Filament\Actions\Admin\UtilityServices\CreateUtilityServiceAction;
use App\Filament\Resources\UtilityServices\UtilityServiceResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Organization;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateUtilityService extends CreateRecord
{
    protected static string $resource = UtilityServiceResource::class;

    protected static string|array $routeMiddleware = 'manager.permission:utility_services,create';

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
            $organization = Organization::query()->findOrFail($organizationId);
        }

        unset($data['organization_id']);

        return app(CreateUtilityServiceAction::class)->handle($organization, $data);
    }

    protected function getRedirectUrl(): string
    {
        return UtilityServiceResource::getUrl('index');
    }
}
