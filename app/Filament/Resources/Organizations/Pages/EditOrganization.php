<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Actions\Superadmin\Organizations\UpdateOrganizationAction;
use App\Enums\SubscriptionPlan;
use App\Filament\Resources\Organizations\OrganizationResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditOrganization extends EditRecord
{
    protected static string $resource = OrganizationResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [
            ...$data,
            'owner_name' => $this->record->owner?->name,
            'owner_email' => $this->record->owner?->email,
            'plan' => $this->record->subscriptions()->latest('expires_at')->value('plan'),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateOrganizationAction::class)->handle($record, [
            ...$data,
            'plan' => filled($data['plan'] ?? null) ? SubscriptionPlan::from($data['plan']) : null,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return OrganizationResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }
}
