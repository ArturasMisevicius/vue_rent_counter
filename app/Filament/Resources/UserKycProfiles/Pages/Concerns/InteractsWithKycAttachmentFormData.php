<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserKycProfiles\Pages\Concerns;

use App\Filament\Actions\Admin\Kyc\SyncKycProfileAttachmentsAction;
use App\Filament\Support\Kyc\KycAttachmentRegistry;
use App\Models\User;
use App\Models\UserKycProfile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

trait InteractsWithKycAttachmentFormData
{
    protected array $attachmentFormData = [];

    protected function attachmentFormDataForRecord(UserKycProfile $record): array
    {
        $data = [];

        foreach (KycAttachmentRegistry::fieldNames() as $field) {
            $attachment = $record->attachments->firstWhere('document_type', KycAttachmentRegistry::documentTypeForField($field));

            $data[$field] = $attachment?->path;

            if ($attachment !== null) {
                Arr::set($data, KycAttachmentRegistry::fileNamesStatePath($field), $attachment->original_filename);
            }
        }

        return $data;
    }

    protected function extractAttachmentFormData(array $data): array
    {
        $attachmentData = [
            'attachment_file_names' => $data['attachment_file_names'] ?? [],
        ];

        unset($data['attachment_file_names']);

        foreach (KycAttachmentRegistry::fieldNames() as $field) {
            $attachmentData[$field] = $data[$field] ?? null;
            unset($data[$field]);
        }

        return [$data, $attachmentData];
    }

    protected function syncKycAttachments(UserKycProfile $record): void
    {
        app(SyncKycProfileAttachmentsAction::class)->handle($record, $this->authenticatedUser(), $this->attachmentFormData);
    }

    private function authenticatedUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
