<?php

namespace App\Filament\Resources\Meters\Pages;

use App\Filament\Actions\Admin\Meters\CreateMeterAction;
use App\Filament\Resources\Meters\MeterResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateMeter extends CreateRecord
{
    protected static string $resource = MeterResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $organization = app(OrganizationContext::class)->currentOrganization();

        if ($organization === null) {
            $user = Auth::user();

            if (! $user instanceof User) {
                abort(403);
            }

            abort_if(! $user->isSuperadmin(), 403);

            $propertyId = (int) ($data['property_id'] ?? 0);
            $organizationId = Property::query()
                ->whereKey($propertyId)
                ->value('organization_id');

            abort_if($organizationId === null, 403);

            $organization = Organization::query()->findOrFail($organizationId);
        }

        return app(CreateMeterAction::class)->handle($organization, $data);
    }

    protected function getRedirectUrl(): string
    {
        return MeterResource::getUrl('index');
    }
}
