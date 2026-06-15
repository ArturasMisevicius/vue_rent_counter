<?php

declare(strict_types=1);

namespace App\Filament\Actions\TenantKyc;

use App\Enums\AuditLogAction;
use App\Enums\TenantDocumentStatus;
use App\Enums\TenantKycDocumentStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\TenantKycDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReplaceTenantKycDocument
{
    public function __construct(
        private readonly SubmitTenantKycDocument $submitTenantKycDocument,
        private readonly CheckTenantKycCompleteness $checkTenantKycCompleteness,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|string|UploadedFile  $fileState
     */
    public function handle(TenantKycDocument $document, User $actor, array $data, array|string|UploadedFile $fileState): TenantKycDocument
    {
        Gate::forUser($actor)->authorize('replace', $document);

        return DB::transaction(function () use ($actor, $data, $document, $fileState): TenantKycDocument {
            $replacement = $this->submitTenantKycDocument->handle($actor, [
                ...$data,
                'organization_id' => $document->organization_id,
                'tenant_id' => $document->tenant_id,
                'document_type' => $document->document_type?->value,
            ], $fileState);

            $before = $document->getOriginal();

            $document->forceFill([
                'status' => TenantKycDocumentStatus::REPLACED,
                'replaced_by_document_id' => $replacement->id,
                'archived_at' => now(),
            ])->save();

            $fileDocument = $document->fileDocument;

            if ($fileDocument !== null) {
                $fileDocument->forceFill([
                    'status' => TenantDocumentStatus::ARCHIVED,
                    'tenant_visible' => false,
                    'archived_at' => now(),
                ])->save();
            }

            $this->auditLogger->record(
                AuditLogAction::ARCHIVED,
                $document,
                [
                    'before' => Arr::except($before, ['document_number_encrypted']),
                    'after' => Arr::except($document->getAttributes(), ['document_number_encrypted']),
                    'context' => ['mutation' => 'tenant_kyc_document.replaced'],
                ],
                $actor->id,
                'Tenant KYC document replaced',
            );

            $this->checkTenantKycCompleteness->handle($document->profile()->firstOrFail(), $actor);

            return $replacement->refresh();
        });
    }
}
