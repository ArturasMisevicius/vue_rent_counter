<?php

namespace App\Filament\Resources\Buildings\Pages;

use App\Filament\Actions\Admin\Buildings\CreateBuildingAction;
use App\Filament\Resources\Buildings\BuildingResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Organization;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateBuilding extends CreateRecord
{
    protected static string $resource = BuildingResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $organization = app(OrganizationContext::class)->currentOrganization();

        if (
            $organization !== null
            && array_key_exists('organization_id', $data)
            && filled($data['organization_id'])
            && (int) $data['organization_id'] !== $organization->id
        ) {
            abort(403);
        }

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

        return app(CreateBuildingAction::class)->handle($organization, $data);
    }

    protected function getRedirectUrl(): string
    {
        return BuildingResource::getUrl('index');
    }
}
