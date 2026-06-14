<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\TenantDocuments;

use App\Enums\AuditLogAction;
use App\Enums\TenantDocumentStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\TenantDocument;
use Illuminate\Database\Eloquent\Collection;

class ExpireTenantDocuments
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(?int $organizationId = null): int
    {
        $expired = 0;

        TenantDocument::query()
            ->select(['id', 'organization_id', 'status', 'expires_at', 'updated_at'])
            ->expiredUnmarked()
            ->when($organizationId !== null, fn ($query) => $query->forOrganization($organizationId))
            ->chunkById(100, function (Collection $documents) use (&$expired): void {
                foreach ($documents as $document) {
                    if (! $document instanceof TenantDocument) {
                        continue;
                    }

                    $before = $document->getOriginal();

                    $document->forceFill(['status' => TenantDocumentStatus::EXPIRED])->save();

                    $this->auditLogger->record(
                        AuditLogAction::UPDATED,
                        $document,
                        [
                            'before' => $before,
                            'after' => $document->getAttributes(),
                            'context' => ['mutation' => 'tenant_document.expired'],
                        ],
                        description: 'Tenant document expired',
                    );

                    $expired++;
                }
            });

        return $expired;
    }
}
