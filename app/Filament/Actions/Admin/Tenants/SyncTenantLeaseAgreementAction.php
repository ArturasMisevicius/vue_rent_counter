<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Tenants;

use App\Filament\Support\Tenants\TenantLeaseAgreement;
use App\Models\Attachment;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SyncTenantLeaseAgreementAction
{
    /**
     * @param  array<string, mixed>  $attachmentData
     */
    public function handle(User $tenant, User $actor, array $attachmentData): void
    {
        if (! $tenant->isTenant() || $tenant->organization_id === null) {
            throw ValidationException::withMessages([
                TenantLeaseAgreement::FIELD => __('validation.exists', [
                    'attribute' => __('admin.tenants.fields.lease_agreement'),
                ]),
            ]);
        }

        $attachment = $tenant->leaseAgreements()->first();
        $path = $this->normalizePath($attachmentData[TenantLeaseAgreement::FIELD] ?? null);
        $originalFilename = Arr::get($attachmentData, TenantLeaseAgreement::fileNamesStatePath());

        if ($path === null) {
            if ($attachment instanceof Attachment) {
                $this->deleteAttachment($attachment);
            }

            return;
        }

        if (($attachment?->path === $path) && (blank($originalFilename) || $attachment?->original_filename === $originalFilename)) {
            return;
        }

        if (($attachment instanceof Attachment) && ($attachment->path !== $path)) {
            $this->deleteAttachment($attachment);
            $attachment = null;
        }

        $storage = $this->storage(TenantLeaseAgreement::DISK);
        $resolvedOriginalFilename = is_string($originalFilename) && $originalFilename !== ''
            ? $originalFilename
            : ($attachment?->original_filename ?: basename($path));

        $record = $attachment ?? new Attachment;
        $record->fill([
            'organization_id' => $tenant->organization_id,
            'uploaded_by_user_id' => $actor->id,
            'filename' => basename($path),
            'original_filename' => $resolvedOriginalFilename,
            'mime_type' => $storage->mimeType($path) ?: 'application/octet-stream',
            'size' => $storage->size($path) ?: 0,
            'disk' => TenantLeaseAgreement::DISK,
            'path' => $path,
            'document_type' => TenantLeaseAgreement::DOCUMENT_TYPE,
            'description' => null,
            'metadata' => null,
        ]);

        $tenant->attachments()->save($record);
    }

    private function normalizePath(mixed $state): ?string
    {
        if (is_array($state)) {
            $state = reset($state);
        }

        if (! is_string($state) || $state === '') {
            return null;
        }

        return $state;
    }

    private function deleteAttachment(Attachment $attachment): void
    {
        $disk = $this->storage((string) $attachment->disk);

        if ($disk->exists((string) $attachment->path)) {
            $disk->delete((string) $attachment->path);
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
