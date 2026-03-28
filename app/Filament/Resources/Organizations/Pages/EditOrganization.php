<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Filament\Actions\Superadmin\Organizations\UpdateOrganizationAction;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\Pages\Concerns\InteractsWithRecordFormValidationExceptions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditOrganization extends EditRecord
{
    use InteractsWithRecordFormValidationExceptions;

    protected static string $resource = OrganizationResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $subscription = $this->record->subscriptions()
            ->select([
                'id',
                'organization_id',
                'plan',
                'status',
                'starts_at',
                'expires_at',
                'is_trial',
                'property_limit_snapshot',
                'tenant_limit_snapshot',
                'meter_limit_snapshot',
                'invoice_limit_snapshot',
            ])
            ->latest('expires_at')
            ->first();

        return [
            ...$data,
            'owner_email' => $this->record->owner?->email,
            'plan' => $subscription?->plan?->value ?? $subscription?->plan,
            'expires_at' => $subscription?->expires_at?->toDateString(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateOrganizationAction::class)->handle($record, $data);
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        try {
            parent::save($shouldRedirect, $shouldSendSavedNotification);
        } catch (ValidationException $exception) {
            $this->addRecordFormValidationErrors($exception);
        }
    }

    public function getTitle(): string
    {
        return __('superadmin.organizations.pages.edit', [
            'name' => $this->record->name,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return OrganizationResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label(__('superadmin.organizations.actions.save_changes'));
    }
}
