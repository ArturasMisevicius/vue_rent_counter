<?php

declare(strict_types=1);

namespace App\Filament\Actions\TenantKyc;

use App\Enums\AuditLogAction;
use App\Enums\TenantDocumentStatus;
use App\Enums\TenantKycDocumentStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\TenantKycDocument;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ApproveKycDocument
{
    public function __construct(
        private readonly CheckTenantKycCompleteness $checkTenantKycCompleteness,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(TenantKycDocument $document, User $actor, ?string $internalNote = null): TenantKycDocument
    {
        Gate::forUser($actor)->authorize('approve', $document);

        return DB::transaction(function () use ($actor, $document, $internalNote): TenantKycDocument {
            $before = $document->getOriginal();

            $document->forceFill([
                'status' => TenantKycDocumentStatus::APPROVED,
                'reviewed_by_user_id' => $actor->id,
                'reviewed_at' => now(),
                'approved_at' => now(),
                'rejected_at' => null,
                'rejection_reason' => null,
                'internal_note' => $internalNote ?: $document->internal_note,
            ])->save();

            $fileDocument = $document->fileDocument;

            if ($fileDocument !== null) {
                $fileDocument->forceFill([
                    'status' => TenantDocumentStatus::VERIFIED,
                    'verified_by_user_id' => $actor->id,
                    'verified_at' => now(),
                    'rejected_by_user_id' => null,
                    'rejected_at' => null,
                    'rejection_reason' => null,
                ])->save();
            }

            $this->auditLogger->record(
                AuditLogAction::APPROVED,
                $document,
                [
                    'before' => Arr::except($before, ['document_number_encrypted']),
                    'after' => Arr::except($document->getAttributes(), ['document_number_encrypted']),
                    'context' => ['mutation' => 'tenant_kyc_document.approved'],
                ],
                $actor->id,
                'Tenant KYC document approved',
            );

            if ($fileDocument !== null) {
                $this->auditLogger->record(
                    AuditLogAction::APPROVED,
                    $fileDocument,
                    [
                        'context' => ['mutation' => 'tenant_kyc_file_document.approved'],
                    ],
                    $actor->id,
                    'Tenant KYC file document approved',
                );
            }

            $this->checkTenantKycCompleteness->handle($document->profile()->firstOrFail(), $actor);

            return $document->refresh();
        });
    }
}
