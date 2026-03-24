<?php

namespace App\Filament\Resources\OrganizationUsers\Pages;

use App\Filament\Resources\OrganizationUsers\OrganizationUserResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use Filament\Resources\Pages\CreateRecord;

class CreateOrganizationUser extends CreateRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = OrganizationUserResource::class;
}
