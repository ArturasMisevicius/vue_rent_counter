<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\TenantDocuments;

use App\Enums\AuditLogAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Filament\Support\TenantDocuments\TenantDocumentFile;
use App\Models\TenantDocument;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReplaceTenantDocumentFile
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>|string|UploadedFile  $fileState
     */
    public function handle(TenantDocument $document, User $actor, array|string|UploadedFile $fileState): TenantDocument
    {
        Gate::forUser($actor)->authorize('replace', $document);

        [$path, $originalFilename] = $this->resolveUpload($fileState, (int) $document->organization_id, (int) $document->tenant_id);

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

        return DB::transaction(function () use ($actor, $document, $originalFilename, $path, $storage): TenantDocument {
            $previousFile = [
                'file_path' => $document->file_path,
                'original_filename' => $document->original_filename,
                'mime_type' => $document->mime_type,
                'size' => $document->size,
            ];

            $document->forceFill([
                'file_path' => $path,
                'original_filename' => $originalFilename ?: basename($path),
                'mime_type' => $storage->mimeType($path) ?: 'application/octet-stream',
                'size' => $storage->size($path) ?: 0,
                'uploaded_by_user_id' => $actor->id,
            ])->save();

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $document,
                [
                    'before' => ['file' => $previousFile],
                    'after' => [
                        'file_path' => $document->file_path,
                        'original_filename' => $document->original_filename,
                        'mime_type' => $document->mime_type,
                        'size' => $document->size,
                    ],
                    'context' => ['mutation' => 'tenant_document.file_replaced'],
                ],
                $actor->id,
                'Tenant document file replaced',
            );

            return $document->refresh();
        });
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
}
