<?php

namespace App\Notifications\Superadmin;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrganizationBroadcastNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Organization $organization,
        private readonly string $title,
        private readonly string $body,
        private readonly string $severity,
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
            'title' => $this->title,
            'body' => $this->body,
            'severity' => $this->severity,
            'organization_id' => $this->organization->getKey(),
            'organization_name' => $this->organization->name,
            'url' => '/app',
        ];
    }
}
