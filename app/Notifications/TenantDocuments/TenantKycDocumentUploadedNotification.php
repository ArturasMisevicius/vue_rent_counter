<?php

declare(strict_types=1);

namespace App\Notifications\TenantDocuments;

use App\Models\TenantDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TenantKycDocumentUploadedNotification extends Notification
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
            'title' => __('notifications.tenant_documents.kyc_uploaded_title'),
            'body' => __('notifications.tenant_documents.kyc_uploaded_body', ['tenant' => $this->document->tenant?->name ?? __('dashboard.not_available')]),
            'url' => route('filament.admin.resources.tenants.view', ['record' => $this->document->tenant_id], false),
            'tenant_document_id' => $this->document->id,
            'organization_id' => $this->document->organization_id,
            'tenant_id' => $this->document->tenant_id,
            'document_type' => $this->document->document_type?->value,
        ];
    }
}
