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
        if (! $this->currentUser()?->isSuperadmin()) {
            return [];
        }

        return [
            CreateAction::make(),
        ];
    }

    private function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
