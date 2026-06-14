<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\TenantDocuments;

use App\Enums\AuditLogAction;
use App\Enums\TenantDocumentStatus;
use App\Enums\TenantDocumentType;
use App\Filament\Support\Audit\AuditLogger;
use App\Filament\Support\TenantDocuments\TenantDocumentFile;
use App\Filament\Support\TenantDocuments\TenantDocumentNotificationRecipients;
use App\Http\Requests\Admin\TenantDocuments\TenantDocumentRequest;
use App\Models\TenantDocument;
use App\Models\User;
use App\Notifications\TenantDocuments\TenantDocumentAvailableNotification;
use App\Notifications\TenantDocuments\TenantDocumentRequiresReviewNotification;
use App\Notifications\TenantDocuments\TenantKycDocumentUploadedNotification;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UploadTenantDocument
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly TenantDocumentNotificationRecipients $recipients,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|string|UploadedFile  $fileState
     */
    public function handle(User $actor, array $data, array|string|UploadedFile $fileState): TenantDocument
    {
        Gate::forUser($actor)->authorize('create', TenantDocument::class);

        $data = $this->normalizeActorScope($actor, $data);
        $organizationId = (int) $data['organization_id'];

        $validated = (new TenantDocumentRequest)
            ->forOrganization($organizationId)
            ->validatePayload([
                'status' => TenantDocumentStatus::ACTIVE->value,
                'tenant_visible' => false,
                ...$data,
            ], $actor);

        $this->ensureTenantUploadIsKycOnly($actor, $validated);

        [$path, $originalFilename] = $this->resolveUpload($fileState, $organizationId, (int) $validated['tenant_id']);

        if ($path === null) {
            throw ValidationException::withMessages([
                TenantDocumentFile::FIELD => __('validation.required', [
                    'attribute' => __('admin.tenant_documents.fields.file'),
                ]),
            ]);
        }

        $storage = $this->storage();

        if (! $storage->exists($path)) {
            throw ValidationException::withMessages([
                TenantDocumentFile::FIELD => __('validation.exists', [
                    'attribute' => __('admin.tenant_documents.fields.file'),
                ]),
            ]);
        }

        return DB::transaction(function () use ($actor, $originalFilename, $path, $storage, $validated): TenantDocument {
            $document = TenantDocument::query()->create([
                ...Arr::only($validated, [
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
                'organization_id' => (int) $actor->organization_id ?: (int) $this->organizationIdFromPayload($validated),
                'file_path' => $path,
                'original_filename' => $originalFilename ?: basename($path),
                'mime_type' => $storage->mimeType($path) ?: 'application/octet-stream',
                'size' => $storage->size($path) ?: 0,
                'uploaded_by_user_id' => $actor->id,
            ]);

            $this->auditLogger->record(
                AuditLogAction::CREATED,
                $document,
                [
                    'after' => $document->getAttributes(),
                    'context' => ['mutation' => 'tenant_document.created'],
                ],
                $actor->id,
                'Tenant document uploaded',
            );

            $fresh = $document->fresh(['tenant']);
            $this->sendUploadNotifications($fresh, $actor);

            return $fresh;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeActorScope(User $actor, array $data): array
    {
        if ($actor->isTenant()) {
            return [
                ...$data,
                'organization_id' => (int) $actor->organization_id,
                'tenant_id' => (int) $actor->id,
                'status' => TenantDocumentStatus::PENDING_REVIEW->value,
                'tenant_visible' => true,
            ];
        }

        $organization = $actor->currentOrganization();
        $organizationId = $actor->isSuperadmin()
            ? ($data['organization_id'] ?? null)
            : $organization?->id;

        if ($organizationId === null) {
            throw ValidationException::withMessages([
                'organization_id' => __('validation.required', ['attribute' => 'organization']),
            ]);
        }

        if (! $actor->isSuperadmin() && isset($data['organization_id']) && (int) $data['organization_id'] !== (int) $organizationId) {
            throw ValidationException::withMessages([
                'organization_id' => __('admin.tenant_documents.messages.organization_mismatch'),
            ]);
        }

        return [
            ...$data,
            'organization_id' => (int) $organizationId,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function ensureTenantUploadIsKycOnly(User $actor, array $validated): void
    {
        if (! $actor->isTenant()) {
            return;
        }

        $type = TenantDocumentType::from((string) $validated['document_type']);

        if (! $type->isKyc()) {
            throw ValidationException::withMessages([
                'document_type' => __('admin.tenant_documents.messages.tenant_upload_kyc_only'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>|string|UploadedFile  $fileState
     * @return array{0: string|null, 1: string|null}
     */
    private function resolveUpload(array|string|UploadedFile $fileState, int $organizationId, int $tenantId): array
    {
        if ($fileState instanceof UploadedFile) {
            $path = $fileState->store(TenantDocumentFile::directory($organizationId, $tenantId), TenantDocumentFile::DISK);

            return [
                is_string($path) ? $path : null,
                $fileState->getClientOriginalName(),
            ];
        }

        if (is_string($fileState)) {
            return [$fileState, basename($fileState)];
        }

        $path = $this->normalizePath($fileState[TenantDocumentFile::FIELD] ?? null);
        $originalFilename = Arr::get($fileState, TenantDocumentFile::fileNamesStatePath());

        return [
            $path,
            is_string($originalFilename) && $originalFilename !== '' ? $originalFilename : null,
        ];
    }

    private function normalizePath(mixed $state): ?string
    {
        if (is_array($state)) {
            $state = reset($state);
        }

        return is_string($state) && $state !== '' ? $state : null;
    }

    private function storage(): FilesystemAdapter
    {
        $storage = Storage::disk(TenantDocumentFile::DISK);

        if (! $storage instanceof FilesystemAdapter) {
            throw new \RuntimeException('Unsupported filesystem adapter.');
        }

        return $storage;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function organizationIdFromPayload(array $validated): int
    {
        $tenant = User::query()
            ->select(['id', 'organization_id'])
            ->findOrFail((int) $validated['tenant_id']);

        return (int) $tenant->organization_id;
    }

    private function sendUploadNotifications(TenantDocument $document, User $actor): void
    {
        if ($document->tenant_visible && $document->tenant instanceof User && ! $actor->isTenant()) {
            $document->tenant->notify(new TenantDocumentAvailableNotification($document));
        }

        if ($document->status === TenantDocumentStatus::PENDING_REVIEW) {
            Notification::send(
                $this->recipients->adminAndManagers((int) $document->organization_id),
                new TenantDocumentRequiresReviewNotification($document),
            );
        }

        if ($actor->isTenant() && $document->isKycDocument()) {
            Notification::send(
                $this->recipients->adminAndManagers((int) $document->organization_id),
                new TenantKycDocumentUploadedNotification($document),
            );
        }
    }
}
