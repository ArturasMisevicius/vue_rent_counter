<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExtraCharges\Pages;

use App\Filament\Actions\Admin\ExtraCharges\CreateExtraChargeAction;
use App\Filament\Resources\ExtraCharges\ExtraChargeResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Organization;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateExtraCharge extends CreateRecord
{
    protected static string $resource = ExtraChargeResource::class;

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

        return app(CreateExtraChargeAction::class)->handle($actor, $organization, $data);
    }

    protected function getRedirectUrl(): string
    {
        return ExtraChargeResource::getUrl('index');
    }
}
