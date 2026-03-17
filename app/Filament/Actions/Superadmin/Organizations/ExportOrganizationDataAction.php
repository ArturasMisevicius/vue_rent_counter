<?php

namespace App\Filament\Actions\Superadmin\Organizations;

use App\Filament\Support\Superadmin\Exports\OrganizationDataExportBuilder;
use App\Models\Organization;

class ExportOrganizationDataAction
{
    public function __construct(
        private readonly OrganizationDataExportBuilder $organizationDataExportBuilder,
    ) {}

    public function handle(Organization $organization): string
    {
        return $this->organizationDataExportBuilder->build($organization);
    }
}
