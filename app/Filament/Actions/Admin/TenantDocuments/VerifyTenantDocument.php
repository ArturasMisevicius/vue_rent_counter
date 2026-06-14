<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\TenantDocuments;

use App\Enums\AuditLogAction;
use App\Enums\TenantDocumentStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\TenantDocument;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class VerifyTenantDocument
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(TenantDocument $document, User $actor): TenantDocument
    {
        Gate::forUser($actor)->authorize('verify', $document);

        $before = $document->getOriginal();

        $document->forceFill([
            'status' => TenantDocumentStatus::VERIFIED,
            'verified_by_user_id' => $actor->id,
            'verified_at' => now(),
            'rejected_by_user_id' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ])->save();

        $this->auditLogger->record(
            AuditLogAction::APPROVED,
            $document,
            [
                'before' => $before,
                'after' => $document->getAttributes(),
                'context' => ['mutation' => 'tenant_document.verified'],
            ],
            $actor->id,
            'Tenant document verified',
        );

        return $document->refresh();
    }
}
