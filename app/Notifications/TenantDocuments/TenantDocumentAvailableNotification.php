<?php

declare(strict_types=1);

namespace App\Notifications\TenantDocuments;

use App\Models\TenantDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TenantDocumentAvailableNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly TenantDocument $document,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->document->document_type?->isRentalContract()
                ? __('notifications.tenant_documents.rental_contract_visible_title')
                : __('notifications.tenant_documents.available_title'),
            'body' => $this->document->document_type?->isRentalContract()
                ? __('notifications.tenant_documents.rental_contract_visible_body', ['title' => $this->document->title])
                : __('notifications.tenant_documents.available_body', ['title' => $this->document->title]),
            'url' => route('filament.admin.pages.tenant-documents', [], false).'#tenant-document-'.$this->document->id,
            'tenant_document_id' => $this->document->id,
            'organization_id' => $this->document->organization_id,
            'tenant_id' => $this->document->tenant_id,
            'document_type' => $this->document->document_type?->value,
        ];
    }
}
