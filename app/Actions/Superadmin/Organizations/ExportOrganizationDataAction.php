<?php

namespace App\Actions\Superadmin\Organizations;

use App\Models\Organization;
use App\Support\Superadmin\Exports\OrganizationDataExportBuilder;

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
