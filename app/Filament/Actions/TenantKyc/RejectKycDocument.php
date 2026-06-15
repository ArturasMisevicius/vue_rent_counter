<?php

declare(strict_types=1);

namespace App\Filament\Actions\TenantKyc;

use App\Enums\AuditLogAction;
use App\Enums\TenantDocumentStatus;
use App\Enums\TenantKycDocumentStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Http\Requests\TenantKyc\RejectKycDocumentRequest;
use App\Models\TenantKycDocument;
use App\Models\User;
use App\Notifications\TenantKyc\TenantKycReplacementRequiredNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RejectKycDocument
{
    public function __construct(
        private readonly CheckTenantKycCompleteness $checkTenantKycCompleteness,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(TenantKycDocument $document, User $actor, array $data): TenantKycDocument
    {
        Gate::forUser($actor)->authorize('reject', $document);

        $validated = (new RejectKycDocumentRequest)->validatePayload($data, $actor);

        return DB::transaction(function () use ($actor, $document, $validated): TenantKycDocument {
            $before = $document->getOriginal();

            $document->forceFill([
                'status' => TenantKycDocumentStatus::REJECTED,
                'reviewed_by_user_id' => $actor->id,
                'reviewed_at' => now(),
                'approved_at' => null,
                'rejected_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
                'internal_note' => $validated['internal_note'] ?? $document->internal_note,
            ])->save();

            $fileDocument = $document->fileDocument;

            if ($fileDocument !== null) {
                $fileDocument->forceFill([
                    'status' => TenantDocumentStatus::REJECTED,
                    'verified_by_user_id' => null,
                    'verified_at' => null,
                    'rejected_by_user_id' => $actor->id,
                    'rejected_at' => now(),
                    'rejection_reason' => $validated['rejection_reason'],
                ])->save();
            }

            $this->auditLogger->record(
                AuditLogAction::REJECTED,
                $document,
                [
                    'before' => Arr::except($before, ['document_number_encrypted']),
                    'after' => Arr::except($document->getAttributes(), ['document_number_encrypted']),
                    'context' => ['mutation' => 'tenant_kyc_document.rejected'],
                ],
                $actor->id,
                'Tenant KYC document rejected',
            );

            $fresh = $document->fresh(['tenant', 'profile']);

            if ($fresh->tenant instanceof User) {
                $fresh->tenant->notify(new TenantKycReplacementRequiredNotification($fresh));
            }

            $this->checkTenantKycCompleteness->handle($fresh->profile, $actor);

            return $fresh;
        });
    }
}
