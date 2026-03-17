<?php

namespace App\Support\Superadmin\Exports;

use App\Models\Organization;

interface OrganizationDataExportBuilder
{
    /**
     * @return array{path: string, download_name: string}
     */
    public function build(Organization $organization): array;
}
