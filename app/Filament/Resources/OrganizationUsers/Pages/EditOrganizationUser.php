<?php

namespace App\Filament\Resources\OrganizationUsers\Pages;

use App\Filament\Resources\OrganizationUsers\OrganizationUserResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditOrganizationUser extends EditRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = OrganizationUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        if (! $this->currentUser()?->isSuperadmin()) {
            return [
                $this->getCancelFormAction(),
            ];
        }

        return parent::getFormActions();
    }

    private function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
