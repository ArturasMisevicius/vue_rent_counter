<?php

namespace App\Notifications\Auth;

use App\Models\OrganizationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly OrganizationInvitation $invitation) {}

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
            ->subject(__('auth.invitation_mail_subject', [
                'organization' => $this->invitation->organization->name,
            ]))
            ->greeting(__('auth.invitation_mail_greeting'))
            ->line(__('auth.invitation_mail_intro', [
                'organization' => $this->invitation->organization->name,
            ]))
            ->action(__('auth.accept_invitation_button'), route('invitation.show', $this->invitation->token))
            ->line(__('auth.invitation_mail_expiry'));
    }
}
