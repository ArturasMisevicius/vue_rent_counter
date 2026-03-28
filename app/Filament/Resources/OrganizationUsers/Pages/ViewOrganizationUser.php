<?php

namespace App\Filament\Resources\OrganizationUsers\Pages;

use App\Filament\Resources\OrganizationUsers\OrganizationUserResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrganizationUser extends ViewRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = OrganizationUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    protected function shouldWrapSuperadminSurface(): bool
    {
        return $this->currentUser()?->isSuperadmin() ?? false;
    }

    private function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
