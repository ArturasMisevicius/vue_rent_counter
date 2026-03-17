<?php

namespace App\Notifications\Superadmin;

use App\Enums\PlatformNotificationSeverity;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class OrganizationMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $title,
        private readonly string $body,
        private readonly PlatformNotificationSeverity $severity,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'preview' => Str::limit($this->body, 120),
            'body' => $this->body,
            'severity' => $this->severity->value,
            'url' => '',
        ];
    }
}
