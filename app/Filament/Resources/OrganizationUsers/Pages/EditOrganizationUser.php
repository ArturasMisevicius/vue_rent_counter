<?php

namespace App\Filament\Resources\OrganizationUsers\Pages;

use App\Filament\Resources\OrganizationUsers\OrganizationUserResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
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
}
