<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\TenantDocuments;

use App\Enums\AuditLogAction;
use App\Enums\TenantDocumentStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Http\Requests\Admin\TenantDocuments\RejectTenantDocumentRequest;
use App\Models\TenantDocument;
use App\Models\User;
use App\Notifications\TenantDocuments\TenantDocumentRejectedNotification;
use Illuminate\Support\Facades\Gate;

class RejectTenantDocument
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(TenantDocument $document, User $actor, string $rejectionReason): TenantDocument
    {
        Gate::forUser($actor)->authorize('reject', $document);

        $validated = (new RejectTenantDocumentRequest)->validatePayload([
            'rejection_reason' => $rejectionReason,
        ], $actor);

        $before = $document->getOriginal();

        $document->forceFill([
            'status' => TenantDocumentStatus::REJECTED,
            'verified_by_user_id' => null,
            'verified_at' => null,
            'rejected_by_user_id' => $actor->id,
            'rejected_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ])->save();

        $this->auditLogger->record(
            AuditLogAction::REJECTED,
            $document,
            [
                'before' => $before,
                'after' => $document->getAttributes(),
                'context' => ['mutation' => 'tenant_document.rejected'],
            ],
            $actor->id,
            'Tenant document rejected',
        );

        $fresh = $document->fresh(['tenant']);

        if ($fresh->isKycDocument() && $fresh->tenant instanceof User) {
            $fresh->tenant->notify(new TenantDocumentRejectedNotification($fresh));
        }

        return $fresh;
    }
}
