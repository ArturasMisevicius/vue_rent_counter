<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantPortalActivatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Organization $organization,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('auth.tenant_portal_activated_subject', [
                'organization' => $this->organization->name,
            ]))
            ->greeting(__('auth.invitation_mail_greeting'))
            ->line(__('auth.tenant_portal_activated_intro', [
                'organization' => $this->organization->name,
            ]))
            ->action(__('tenant.navigation.home'), route('tenant.home'))
            ->line(__('auth.tenant_portal_activated_outro'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'tenant_portal_activated',
            'organization_id' => $this->organization->id,
            'organization_name' => $this->organization->name,
        ];
    }
}
