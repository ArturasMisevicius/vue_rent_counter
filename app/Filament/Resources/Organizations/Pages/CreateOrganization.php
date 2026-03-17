<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Actions\Superadmin\Organizations\CreateOrganizationAction;
use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Filament\Resources\Organizations\OrganizationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOrganization extends CreateRecord
{
    protected static string $resource = OrganizationResource::class;

    public function getTitle(): string
    {
        return 'Create organization';
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateOrganizationAction::class)->handle(auth()->user(), [
            ...$data,
            'plan' => SubscriptionPlan::from($data['plan']),
            'duration' => SubscriptionDuration::from($data['duration']),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return OrganizationResource::getUrl('index');
    }
}
