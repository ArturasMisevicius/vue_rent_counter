<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\TenantDocuments;

use App\Enums\AuditLogAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Http\Requests\Admin\TenantDocuments\TenantDocumentRequest;
use App\Models\TenantDocument;
use App\Models\User;
use App\Notifications\TenantDocuments\TenantDocumentAvailableNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdateTenantDocumentMetadata
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(TenantDocument $document, User $actor, array $data): TenantDocument
    {
        Gate::forUser($actor)->authorize('update', $document);

        $validated = (new TenantDocumentRequest)
            ->forOrganization((int) $document->organization_id)
            ->validatePayload([
                ...$document->only([
                    'tenant_id',
                    'property_id',
                    'related_type',
                    'related_id',
                    'document_type',
                    'title',
                    'description_for_tenant',
                    'internal_note',
                    'status',
                    'tenant_visible',
                    'expires_at',
                ]),
                ...$data,
            ], $actor);

        return DB::transaction(function () use ($actor, $document, $validated): TenantDocument {
            $before = $document->getOriginal();
            $wasVisible = (bool) $document->tenant_visible;

            $document->fill(Arr::only($validated, [
                'tenant_id',
                'property_id',
                'related_type',
                'related_id',
                'document_type',
                'title',
                'description_for_tenant',
                'internal_note',
                'status',
                'tenant_visible',
                'expires_at',
            ]));
            $document->save();

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $document,
                [
                    'before' => $before,
                    'after' => $document->getAttributes(),
                    'context' => ['mutation' => 'tenant_document.metadata_updated'],
                ],
                $actor->id,
                'Tenant document metadata updated',
            );

            $fresh = $document->fresh(['tenant']);

            if (! $wasVisible && $fresh->tenant_visible && $fresh->tenant instanceof User) {
                $fresh->tenant->notify(new TenantDocumentAvailableNotification($fresh));
            }

            return $fresh;
        });
    }
}
