<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Filament\Resources\Organizations\OrganizationResource;
use Filament\Resources\Pages\ViewRecord;

class ViewOrganization extends ViewRecord
{
    protected static string $resource = OrganizationResource::class;

    public function getTitle(): string
    {
        return 'Organization Overview';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
