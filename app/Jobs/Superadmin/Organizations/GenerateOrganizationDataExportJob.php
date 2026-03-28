<?php

declare(strict_types=1);

namespace App\Jobs\Superadmin\Organizations;

use App\Enums\AuditLogAction;
use App\Filament\Actions\Superadmin\Organizations\ExportOrganizationDataAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\Superadmin\OrganizationDataExportReadyNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class GenerateOrganizationDataExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $organizationId,
        public string $reason,
        public ?int $requestedByUserId = null,
    ) {}

    public function handle(
        ExportOrganizationDataAction $exportOrganizationDataAction,
        AuditLogger $auditLogger,
    ): void {
        $organization = Organization::query()
            ->select(['id', 'name', 'slug', 'status', 'owner_user_id'])
            ->withOwnerSummary()
            ->find($this->organizationId);

        if (! $organization instanceof Organization) {
            return;
        }

        $owner = $organization->owner;

        if (! $owner instanceof User || blank($owner->email)) {
            return;
        }

        $exportPath = $exportOrganizationDataAction->handle($organization);

        $owner->notify(new OrganizationDataExportReadyNotification(
            $organization,
            $exportPath,
            $this->reason,
        ));

        $auditLogger->record(
            AuditLogAction::EXPORTED,
            $organization,
            [
                'reason' => $this->reason,
                'delivery' => 'owner_email',
                'owner_user_id' => $owner->id,
                'owner_email' => $owner->email,
                'export_path' => basename($exportPath),
            ],
            $this->requestedByUserId,
            'Organization data export generated',
        );
    }
}
