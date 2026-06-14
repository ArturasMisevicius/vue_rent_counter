<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\TenantDocuments;

use App\Enums\AuditLogAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\TenantDocument;
use App\Models\User;
use App\Notifications\TenantDocuments\TenantDocumentAvailableNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ToggleTenantDocumentVisibility
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(TenantDocument $document, User $actor, bool $visible): TenantDocument
    {
        Gate::forUser($actor)->authorize('update', $document);

        if ($visible && blank($document->description_for_tenant)) {
            throw ValidationException::withMessages([
                'description_for_tenant' => __('admin.tenant_documents.messages.tenant_safe_metadata_required'),
            ]);
        }

        $before = $document->getOriginal();

        $document->forceFill(['tenant_visible' => $visible])->save();

        $this->auditLogger->record(
            AuditLogAction::UPDATED,
            $document,
            [
                'before' => $before,
                'after' => $document->getAttributes(),
                'context' => ['mutation' => 'tenant_document.visibility_updated'],
            ],
            $actor->id,
            'Tenant document visibility updated',
        );

        $fresh = $document->fresh(['tenant']);

        if ($visible && $fresh->tenant instanceof User) {
            $fresh->tenant->notify(new TenantDocumentAvailableNotification($fresh));
        }

        return $fresh;
    }
}
