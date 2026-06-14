<?php

declare(strict_types=1);

namespace App\Notifications\Admin;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ManagerInvitationAcceptedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly User $manager,
        private readonly Organization $organization,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('admin.organization_users.notifications.accepted_subject', [
                'organization' => $this->organization->name,
            ]))
            ->greeting(__('admin.manager_permissions.notifications.greeting'))
            ->line(__('admin.organization_users.notifications.accepted_body', [
                'manager' => $this->manager->name,
                'email' => $this->manager->email,
                'organization' => $this->organization->name,
            ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('admin.organization_users.notifications.accepted_title'),
            'body' => __('admin.organization_users.notifications.accepted_body', [
                'manager' => $this->manager->name,
                'email' => $this->manager->email,
                'organization' => $this->organization->name,
            ]),
            'manager_id' => $this->manager->id,
            'organization_id' => $this->organization->id,
        ];
    }
}
