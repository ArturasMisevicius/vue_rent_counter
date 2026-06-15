<?php

declare(strict_types=1);

namespace App\Filament\Actions\TenantKyc;

use App\Filament\Support\TenantDocuments\TenantDocumentNotificationRecipients;
use App\Models\TenantKycDocument;
use App\Models\User;
use App\Notifications\TenantKyc\TenantKycDocumentExpiresSoonNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

class SendKycReminder
{
    public function __construct(
        private readonly TenantDocumentNotificationRecipients $recipients,
    ) {}

    public function handle(?int $organizationId = null, int $days = 30): int
    {
        $sent = 0;

        TenantKycDocument::query()
            ->select([
                'id',
                'organization_id',
                'tenant_id',
                'kyc_profile_id',
                'document_type',
                'expires_at',
                'status',
                'file_document_id',
                'updated_at',
            ])
            ->expiringSoon($days)
            ->when($organizationId !== null, fn ($query) => $query->forOrganization($organizationId))
            ->withReviewRelations()
            ->chunkById(100, function (Collection $documents) use (&$sent): void {
                foreach ($documents as $document) {
                    if (! $document instanceof TenantKycDocument) {
                        continue;
                    }

                    if ($document->tenant instanceof User) {
                        $document->tenant->notify(new TenantKycDocumentExpiresSoonNotification($document));
                    }

                    Notification::send(
                        $this->recipients->adminAndManagers((int) $document->organization_id),
                        new TenantKycDocumentExpiresSoonNotification($document),
                    );

                    $sent++;
                }
            });

        return $sent;
    }
}
