<?php

namespace App\Notifications\Admin;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ManagerPermissionsUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly User $actor,
        private readonly Organization $organization,
        private readonly string $messageKey = 'admin.manager_permissions.notifications.updated',
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('admin.manager_permissions.notifications.subject', [
                'organization' => $this->organization->name,
            ]))
            ->greeting(__('admin.manager_permissions.notifications.greeting'))
            ->line(__($this->messageKey, [
                'actor' => $this->actor->name,
                'organization' => $this->organization->name,
            ]));
    }
}
