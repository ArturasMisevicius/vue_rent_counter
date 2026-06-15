<?php

declare(strict_types=1);

namespace App\Filament\Actions\TenantKyc;

use App\Enums\AuditLogAction;
use App\Enums\TenantDocumentStatus;
use App\Enums\TenantKycDocumentStatus;
use App\Enums\TenantKycProfileStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\TenantKycDocument;
use Illuminate\Database\Eloquent\Collection;

class ExpireKycDocuments
{
    public function __construct(
        private readonly CheckTenantKycCompleteness $checkTenantKycCompleteness,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(?int $organizationId = null): int
    {
        $expired = 0;

        TenantKycDocument::query()
            ->select([
                'id',
                'organization_id',
                'tenant_id',
                'kyc_profile_id',
                'status',
                'file_document_id',
                'expires_at',
                'updated_at',
            ])
            ->expiredUnmarked()
            ->when($organizationId !== null, fn ($query) => $query->forOrganization($organizationId))
            ->with(['fileDocument:id,organization_id,status', 'profile:id,organization_id,tenant_id,status'])
            ->chunkById(100, function (Collection $documents) use (&$expired): void {
                foreach ($documents as $document) {
                    if (! $document instanceof TenantKycDocument) {
                        continue;
                    }

                    $before = $document->getOriginal();

                    $document->forceFill(['status' => TenantKycDocumentStatus::EXPIRED])->save();

                    if ($document->fileDocument !== null) {
                        $document->fileDocument->forceFill(['status' => TenantDocumentStatus::EXPIRED])->save();
                    }

                    $this->auditLogger->record(
                        AuditLogAction::UPDATED,
                        $document,
                        [
                            'before' => $before,
                            'after' => $document->getAttributes(),
                            'context' => ['mutation' => 'tenant_kyc_document.expired'],
                        ],
                        description: 'Tenant KYC document expired',
                    );

                    if ($document->profile !== null) {
                        $document->profile->forceFill([
                            'status' => TenantKycProfileStatus::EXPIRED,
                            'expires_at' => now(),
                        ])->save();

                        $this->checkTenantKycCompleteness->handle($document->profile);
                    }

                    $expired++;
                }
            });

        return $expired;
    }
}
