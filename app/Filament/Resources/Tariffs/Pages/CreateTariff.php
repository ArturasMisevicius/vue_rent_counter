<?php

namespace App\Filament\Resources\Tariffs\Pages;

use App\Filament\Actions\Admin\Tariffs\CreateTariffAction;
use App\Filament\Resources\Tariffs\TariffResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Organization;
use App\Models\Provider;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateTariff extends CreateRecord
{
    protected static string $resource = TariffResource::class;

    protected static string|array $routeMiddleware = 'manager.permission:tariffs,create';

    protected function handleRecordCreation(array $data): Model
    {
        $organization = app(OrganizationContext::class)->currentOrganization();

        if ($organization === null) {
            $user = Auth::user();

            if (! $user instanceof User) {
                abort(403);
            }

            abort_if(! $user->isSuperadmin(), 403);

            $providerId = (int) ($data['provider_id'] ?? 0);
            $organizationId = Provider::query()->whereKey($providerId)->value('organization_id');

            abort_if($organizationId === null, 403);

            $organization = Organization::query()->findOrFail($organizationId);
        }

        return app(CreateTariffAction::class)->handle($organization, $data);
    }

    protected function getRedirectUrl(): string
    {
        return TariffResource::getUrl('index');
    }
}
