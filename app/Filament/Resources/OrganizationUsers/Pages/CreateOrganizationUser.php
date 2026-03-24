<?php

namespace App\Filament\Resources\OrganizationUsers\Pages;

use App\Filament\Resources\OrganizationUsers\OrganizationUserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrganizationUser extends CreateRecord
{
    protected static string $resource = OrganizationUserResource::class;
}
