<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Filament\Actions\Superadmin\Organizations\CreateOrganizationAction;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\Pages\Concerns\InteractsWithRecordFormValidationExceptions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateOrganization extends CreateRecord
{
    use InteractsWithRecordFormValidationExceptions;

    protected static string $resource = OrganizationResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return __('superadmin.organizations.pages.new');
    }

    public function create(bool $another = false): void
    {
        try {
            parent::create($another);
        } catch (ValidationException $exception) {
            $this->addRecordFormValidationErrors($exception);
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateOrganizationAction::class)->handle(auth()->user(), $data);
    }

    protected function getRedirectUrl(): string
    {
        return OrganizationResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label(__('superadmin.organizations.actions.save'));
    }
}
