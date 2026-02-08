<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Models\Attachment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

final class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure tenant_id is unique if not provided
        if (empty($data['tenant_id'])) {
            $data['tenant_id'] = 'T' . str_pad((string) (rand(10000, 99999)), 5, '0', STR_PAD_LEFT);
        }

        // Store file upload data for later processing
        $this->fileUploads = [
            'tenant_photo' => $data['tenant_photo'] ?? null,
            'lease_contract' => $data['lease_contract'] ?? null,
            'identity_documents' => $data['identity_documents'] ?? [],
            'other_documents' => $data['other_documents'] ?? [],
        ];

        // Remove file upload fields from data to prevent database errors
        unset($data['tenant_photo'], $data['lease_contract'], $data['identity_documents'], $data['other_documents']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->processFileUploads();
    }

    private function processFileUploads(): void
    {
        $tenant = $this->getRecord();
        
        // Process tenant photo
        if ($this->fileUploads['tenant_photo']) {
            $this->createAttachment(
                $tenant,
                $this->fileUploads['tenant_photo'],
                'photo',
                'Tenant photo'
            );
        }

        // Process lease contract
        if ($this->fileUploads['lease_contract']) {
            $this->createAttachment(
                $tenant,
                $this->fileUploads['lease_contract'],
                'contract',
                'Lease contract'
            );
        }

        // Process identity documents
        foreach ($this->fileUploads['identity_documents'] as $file) {
            $this->createAttachment(
                $tenant,
                $file,
                'identity',
                'Identity document'
            );
        }

        // Process other documents
        foreach ($this->fileUploads['other_documents'] as $file) {
            $this->createAttachment(
                $tenant,
                $file,
                'document',
                'Document'
            );
        }
    }

    private function createAttachment($tenant, $file, string $category, string $description): void
    {
        if (!$file) return;

        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs("tenants/{$category}s", $filename, 'private');

        Attachment::create([
            'tenant_id' => $tenant->tenant_id,
            'attachable_id' => $tenant->id,
            'attachable_type' => get_class($tenant),
            'uploaded_by' => auth()->id(),
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'disk' => 'private',
            'path' => $path,
            'description' => $description,
            'metadata' => [
                'category' => $category,
            ],
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    private array $fileUploads = [];
}