<?php

declare(strict_types=1);

namespace App\Notifications\TenantKyc;

use App\Models\TenantKycDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TenantKycDocumentExpiresSoonNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly TenantKycDocument $document,
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
        $days = $this->document->expires_at?->diffInDays(now()) ?? 0;

        return [
            'title' => __('notifications.tenant_kyc.expires_soon_title'),
            'body' => __('notifications.tenant_kyc.expires_soon_body', [
                'type' => $this->document->document_type?->label() ?? __('tenant.pages.verification.document'),
                'days' => max(0, (int) $days),
            ]),
            'url' => '/tenant/verification',
            'tenant_kyc_document_id' => $this->document->id,
            'tenant_kyc_profile_id' => $this->document->kyc_profile_id,
            'organization_id' => $this->document->organization_id,
            'tenant_id' => $this->document->tenant_id,
            'document_type' => $this->document->document_type?->value,
            'expires_at' => $this->document->expires_at?->toDateString(),
        ];
    }
}
