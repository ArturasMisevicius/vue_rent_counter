<?php

declare(strict_types=1);

namespace App\Filament\Actions\Superadmin\Organizations;

use App\Jobs\Superadmin\Organizations\GenerateOrganizationDataExportJob;
use App\Models\Organization;

final class QueueOrganizationDataExportAction
{
    public function handle(Organization $organization, string $reason, ?int $requestedByUserId = null): void
    {
        GenerateOrganizationDataExportJob::dispatch(
            $organization->id,
            $reason,
            $requestedByUserId ?? auth()->id(),
        );
    }
}
