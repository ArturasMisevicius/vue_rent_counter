<?php

namespace App\Filament\Resources\OrganizationUsers\Pages;

use App\Filament\Resources\OrganizationUsers\OrganizationUserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrganizationUsers extends ListRecords
{
    protected static string $resource = OrganizationUserResource::class;

    protected function getHeaderActions(): array
    {
        if (! OrganizationUserResource::canCreate()) {
            return [];
        }

        $createAction = CreateAction::make();

        if (! $this->currentUser()?->isSuperadmin()) {
            $createAction->label(__('admin.organization_users.actions.invite_manager'));
        }

        return [
            $createAction,
        ];
    }

    private function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
