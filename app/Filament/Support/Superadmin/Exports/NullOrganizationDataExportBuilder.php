<?php

namespace App\Filament\Support\Superadmin\Exports;

use App\Models\Organization;
use ZipArchive;

class NullOrganizationDataExportBuilder extends OrganizationDataExportBuilder
{
    public function build(Organization $organization): string
    {
        $path = storage_path('app/exports/organization-'.$organization->id.'-empty-'.now()->timestamp.'.zip');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $archive = new ZipArchive;
        $archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $archive->addFromString('properties.csv', "id,name\n");
        $archive->addFromString('billing.csv', "id,reference\n");
        $archive->close();

        return $path;
    }
}
