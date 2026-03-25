<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Kyc;

use App\Filament\Support\Kyc\KycAttachmentRegistry;
use App\Models\Attachment;
use App\Models\User;
use App\Models\UserKycProfile;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class SyncKycProfileAttachmentsAction
{
    public function handle(UserKycProfile $profile, User $actor, array $attachmentData): void
    {
        foreach (KycAttachmentRegistry::fieldNames() as $field) {
            $documentType = KycAttachmentRegistry::documentTypeForField($field);
            $attachment = $profile->attachments()->forDocumentType($documentType)->first();
            $path = $this->normalizePath($attachmentData[$field] ?? null);
            $originalFilename = Arr::get($attachmentData, KycAttachmentRegistry::fileNamesStatePath($field));

            if ($path === null) {
                if ($attachment instanceof Attachment) {
                    $this->deleteAttachment($attachment);
                }

                continue;
            }

            if (($attachment?->path === $path) && (blank($originalFilename) || $attachment?->original_filename === $originalFilename)) {
                continue;
            }

            if (($attachment instanceof Attachment) && ($attachment->path !== $path)) {
                $this->deleteAttachment($attachment);
                $attachment = null;
            }

            $disk = 'local';
            $storage = $this->storage($disk);
            $resolvedOriginalFilename = is_string($originalFilename) && $originalFilename !== ''
                ? $originalFilename
                : ($attachment?->original_filename ?: basename($path));

            $record = $attachment ?? new Attachment;
            $record->fill([
                'organization_id' => $profile->organization_id,
                'uploaded_by_user_id' => $actor->id,
                'filename' => basename($path),
                'original_filename' => $resolvedOriginalFilename,
                'mime_type' => $storage->mimeType($path) ?: 'application/octet-stream',
                'size' => $storage->size($path) ?: 0,
                'disk' => $disk,
                'path' => $path,
                'document_type' => $documentType,
                'description' => null,
                'metadata' => null,
            ]);

            $profile->attachments()->save($record);
        }
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
