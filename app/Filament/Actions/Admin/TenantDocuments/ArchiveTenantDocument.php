<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\TenantDocuments;

use App\Enums\AuditLogAction;
use App\Enums\TenantDocumentStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\TenantDocument;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ArchiveTenantDocument
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(TenantDocument $document, User $actor): TenantDocument
    {
        Gate::forUser($actor)->authorize('archive', $document);

        $before = $document->getOriginal();

        $document->forceFill([
            'status' => TenantDocumentStatus::ARCHIVED,
            'tenant_visible' => false,
            'archived_at' => now(),
        ])->save();

        $this->auditLogger->record(
            AuditLogAction::ARCHIVED,
            $document,
            [
                'before' => $before,
                'after' => $document->getAttributes(),
                'context' => ['mutation' => 'tenant_document.archived'],
            ],
            $actor->id,
            'Tenant document archived',
        );

        return $document->refresh();
    }
}
