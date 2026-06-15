<?php

declare(strict_types=1);

namespace App\Notifications\TenantKyc;

use App\Models\TenantKycProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TenantKycRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly TenantKycProfile $profile,
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
            'title' => __('notifications.tenant_kyc.rejected_title'),
            'body' => __('notifications.tenant_kyc.rejected_body'),
            'url' => '/tenant/verification',
            'tenant_kyc_profile_id' => $this->profile->id,
            'organization_id' => $this->profile->organization_id,
            'tenant_id' => $this->profile->tenant_id,
            'rejection_reason' => $this->profile->rejection_reason,
        ];
    }
}
