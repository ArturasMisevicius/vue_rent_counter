<?php

namespace App\Actions\Superadmin\Organizations;

use App\Enums\AuditLogAction;
use App\Models\Organization;
use App\Support\Audit\AuditLogger;
use App\Support\Superadmin\Exports\OrganizationDataExportBuilder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportOrganizationDataAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly OrganizationDataExportBuilder $organizationDataExportBuilder,
    ) {}

    public function __invoke(Organization $organization): BinaryFileResponse
    {
        $export = $this->organizationDataExportBuilder->build($organization);

        $this->auditLogger->log(
            AuditLogAction::EXPORTED,
            $organization,
            'Organization data export prepared.',
            [
                'download_name' => $export['download_name'],
            ],
        );

        return response()->download($export['path'], $export['download_name'])->deleteFileAfterSend(true);
    }
}
