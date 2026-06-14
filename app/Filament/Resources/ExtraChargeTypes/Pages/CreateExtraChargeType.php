<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExtraChargeTypes\Pages;

use App\Filament\Actions\Admin\ExtraCharges\CreateExtraChargeTypeAction;
use App\Filament\Resources\ExtraChargeTypes\ExtraChargeTypeResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Organization;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateExtraChargeType extends CreateRecord
{
    protected static string $resource = ExtraChargeTypeResource::class;

    protected static string|array $routeMiddleware = 'manager.permission:extra_charges,create';

    protected function handleRecordCreation(array $data): Model
    {
        $actor = Auth::user();

        if (! $actor instanceof User) {
            abort(403);
        }

        $organization = app(OrganizationContext::class)->currentOrganization();

        if (! $organization instanceof Organization) {
            $organization = Organization::query()->findOrFail((int) ($data['organization_id'] ?? 0));
        }

        unset($data['organization_id']);

        return app(CreateExtraChargeTypeAction::class)->handle($actor, $organization, $data);
    }

    protected function getRedirectUrl(): string
    {
        return ExtraChargeTypeResource::getUrl('index');
    }
}
