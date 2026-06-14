<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use App\Models\OrganizationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class OrganizationInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly OrganizationInvitation $invitation,
        private readonly ?string $routeToken = null,
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
        $message = (new MailMessage)
            ->subject(__('auth.invitation_mail_subject', [
                'organization' => $this->invitation->organization->name,
            ]))
            ->greeting(__('auth.invitation_mail_greeting'))
            ->line(__('auth.invitation_mail_intro', [
                'organization' => $this->invitation->organization->name,
            ]));

        if ($this->invitation->tenant !== null) {
            $message
                ->line(__('auth.invitation_mail_tenant_name', [
                    'tenant' => $this->invitation->tenant->name,
                ]))
                ->line(__('auth.invitation_mail_tenant_portal_explanation'));
        }

        return $message
            ->action(__('auth.accept_invitation_button'), route('invitation.show', $this->routeToken ?? $this->invitation->routeToken()))
            ->line(__('auth.invitation_mail_expiry', [
                'expires_at' => $this->expiresAtLabel(),
            ]));
    }

    private function expiresAtLabel(): string
    {
        $expiresAt = $this->invitation->expires_at;

        if (! $expiresAt instanceof Carbon) {
            return __('auth.invitation_mail_expiry_unknown');
        }

        return $expiresAt
            ->locale(app()->getLocale())
            ->translatedFormat('Y-m-d H:i');
    }
}
