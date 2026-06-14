<?php

namespace App\Filament\Actions\Admin\RentalContracts;

use App\Enums\AuditLogAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Filament\Support\RentalContracts\RentalContractFile;
use App\Models\Attachment;
use App\Models\RentalContract;
use App\Models\User;
use App\Notifications\RentalContracts\RentalContractAvailableNotification;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UploadRentalContractFileAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>|string|UploadedFile  $fileState
     */
    public function handle(RentalContract $contract, User $actor, array|string|UploadedFile $fileState): Attachment
    {
        Gate::forUser($actor)->authorize('upload', $contract);

        [$path, $originalFilename] = $this->resolveUpload($fileState);

        if ($path === null) {
            throw ValidationException::withMessages([
                RentalContractFile::FIELD => __('validation.required', [
                    'attribute' => __('admin.rental_contracts.fields.file'),
                ]),
            ]);
        }

        $storage = $this->storage(RentalContractFile::DISK);

        if (! $storage->exists($path)) {
            throw ValidationException::withMessages([
                RentalContractFile::FIELD => __('validation.exists', [
                    'attribute' => __('admin.rental_contracts.fields.file'),
                ]),
            ]);
        }

        $previous = $contract->file;

        if ($previous instanceof Attachment && $previous->path !== $path) {
            $this->deleteAttachment($previous);
        }

        $attachment = $previous instanceof Attachment && $previous->path === $path
            ? $previous
            : new Attachment;

        $attachment->fill([
            'organization_id' => $contract->organization_id,
            'uploaded_by_user_id' => $actor->id,
            'filename' => basename($path),
            'original_filename' => $originalFilename ?: basename($path),
            'mime_type' => $storage->mimeType($path) ?: 'application/octet-stream',
            'size' => $storage->size($path) ?: 0,
            'disk' => RentalContractFile::DISK,
            'path' => $path,
            'document_type' => RentalContractFile::DOCUMENT_TYPE,
            'description' => null,
            'metadata' => [
                'contract_number' => $contract->contract_number,
            ],
        ]);

        $contract->attachments()->save($attachment);

        $contract->forceFill(['updated_by_user_id' => $actor->id])->save();

        $this->auditLogger->record(
            AuditLogAction::UPDATED,
            $contract,
            [
                'after' => [
                    'attachment_id' => $attachment->id,
                    'path' => $attachment->path,
                    'original_filename' => $attachment->original_filename,
                ],
                'context' => ['mutation' => 'rental_contract.file_uploaded'],
            ],
            $actor->id,
            'Rental contract file uploaded',
        );

        if ($contract->tenant_visible && $contract->tenant instanceof User) {
            $contract->tenant->notify(new RentalContractAvailableNotification($contract));
        }

        return $attachment->refresh();
    }

    /**
     * @param  array<string, mixed>|string|UploadedFile  $fileState
     * @return array{0: string|null, 1: string|null}
     */
    private function resolveUpload(array|string|UploadedFile $fileState): array
    {
        if ($fileState instanceof UploadedFile) {
            return [
                $fileState->store(RentalContractFile::DIRECTORY, RentalContractFile::DISK),
                $fileState->getClientOriginalName(),
            ];
        }

        if (is_string($fileState)) {
            return [$fileState, basename($fileState)];
        }

        $path = $this->normalizePath($fileState[RentalContractFile::FIELD] ?? null);
        $originalFilename = Arr::get($fileState, RentalContractFile::fileNamesStatePath());

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

    private function deleteAttachment(Attachment $attachment): void
    {
        $storage = $this->storage((string) $attachment->disk);

        if ($storage->exists((string) $attachment->path)) {
            $storage->delete((string) $attachment->path);
        }

        $attachment->delete();
    }

    private function storage(string $disk): FilesystemAdapter
    {
        $storage = Storage::disk($disk);

        if (! $storage instanceof FilesystemAdapter) {
            throw new \RuntimeException('Unsupported filesystem adapter.');
        }

        return $storage;
    }
}
