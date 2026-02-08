<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Models\Attachment;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

final class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load existing attachments for display
        $tenant = $this->getRecord();
        
        // Get existing files by category
        $photo = $tenant->photo();
        $contract = $tenant->leaseContract();
        $identityDocs = $tenant->identityDocuments()->get();
        $otherDocs = $tenant->documents()->where('metadata->category', 'document')->get();

        // Set file upload fields to show existing files
        if ($photo) {
            $data['tenant_photo'] = [$photo->path];
        }
        
        if ($contract) {
            $data['lease_contract'] = [$contract->path];
        }
        
        if ($identityDocs->isNotEmpty()) {
            $data['identity_documents'] = $identityDocs->pluck('path')->toArray();
        }
        
        if ($otherDocs->isNotEmpty()) {
            $data['other_documents'] = $otherDocs->pluck('path')->toArray();
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
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

    protected function afterSave(): void
    {
        $this->processFileUploads();
    }

    private function processFileUploads(): void
    {
        $tenant = $this->getRecord();
        
        // Process tenant photo
        if ($this->fileUploads['tenant_photo']) {
            // Remove old photo
            $oldPhoto = $tenant->photo();
            if ($oldPhoto) {
                $oldPhoto->delete();
            }
            
            $this->createAttachment(
                $tenant,
                $this->fileUploads['tenant_photo'],
                'photo',
                'Tenant photo'
            );
        }

        // Process lease contract
        if ($this->fileUploads['lease_contract']) {
            // Remove old contract
            $oldContract = $tenant->leaseContract();
            if ($oldContract) {
                $oldContract->delete();
            }
            
            $this->createAttachment(
                $tenant,
                $this->fileUploads['lease_contract'],
                'contract',
                'Lease contract'
            );
        }

        // Process identity documents (append new ones)
        foreach ($this->fileUploads['identity_documents'] as $file) {
            if (is_object($file)) { // Only process new uploads
                $this->createAttachment(
                    $tenant,
                    $file,
                    'identity',
                    'Identity document'
                );
            }
        }

        // Process other documents (append new ones)
        foreach ($this->fileUploads['other_documents'] as $file) {
            if (is_object($file)) { // Only process new uploads
                $this->createAttachment(
                    $tenant,
                    $file,
                    'document',
                    'Document'
                );
            }
        }
    }

    private function createAttachment($tenant, $file, string $category, string $description): void
    {
        if (!$file || !is_object($file)) return;

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