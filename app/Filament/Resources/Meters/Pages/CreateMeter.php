<?php

namespace App\Filament\Resources\Meters\Pages;

use App\Actions\Admin\Meters\CreateMeterAction;
use App\Filament\Resources\Meters\MeterResource;
use App\Support\Admin\OrganizationContext;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateMeter extends CreateRecord
{
    protected static string $resource = MeterResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $organization = app(OrganizationContext::class)->currentOrganization();

        abort_if($organization === null, 403);

        return app(CreateMeterAction::class)->handle($organization, $data);
    }

    protected function getRedirectUrl(): string
    {
        return MeterResource::getUrl('index');
    }
}
