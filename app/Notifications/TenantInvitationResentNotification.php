<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\OrganizationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantInvitationResentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly OrganizationInvitation $invitation,
        private readonly ?string $routeToken = null,
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
            ->subject(__('auth.tenant_invitation_resent_subject', [
                'organization' => $this->invitation->organization->name,
            ]))
            ->greeting(__('auth.invitation_mail_greeting'))
            ->line(__('auth.tenant_invitation_resent_intro', [
                'organization' => $this->invitation->organization->name,
            ]))
            ->line(__('auth.invitation_mail_tenant_portal_explanation'))
            ->action(__('auth.accept_invitation_button'), route('invitation.show', $this->routeToken ?? $this->invitation->routeToken()))
            ->line(__('auth.invitation_mail_expiry', [
                'expires_at' => $this->invitation->expires_at?->locale(app()->getLocale())->translatedFormat('Y-m-d H:i') ?? __('auth.invitation_mail_expiry_unknown'),
            ]));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'tenant_invitation_resent',
            'invitation_id' => $this->invitation->id,
            'tenant_id' => $this->invitation->tenant_id,
        ];
    }
}
