<?php

declare(strict_types=1);

namespace App\Notifications\Superadmin;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class OrganizationOwnershipTransferredNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Organization $organization,
        public readonly User $previousOwner,
        public readonly User $newOwner,
        public readonly string $recipientRole,
        public readonly string $reason,
    ) {}

    /**
     * @return array<int, string>
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
            'title' => __('superadmin.organizations.notifications.ownership_transferred'),
            'organization_id' => $this->organization->getKey(),
            'organization_name' => $this->organization->name,
            'previous_owner_user_id' => $this->previousOwner->id,
            'previous_owner_name' => $this->previousOwner->name,
            'new_owner_user_id' => $this->newOwner->id,
            'new_owner_name' => $this->newOwner->name,
            'recipient_role' => $this->recipientRole,
            'reason' => $this->reason,
        ];
    }
}
