<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Filament\Resources\Tenants\TenantResource;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantInvitationAcceptedAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly User $tenant,
        private readonly OrganizationInvitation $invitation,
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
            ->subject(__('auth.tenant_invitation_accepted_admin_subject', [
                'tenant' => $this->tenant->name,
            ]))
            ->line(__('auth.tenant_invitation_accepted_admin_intro', [
                'tenant' => $this->tenant->name,
                'email' => $this->tenant->email,
            ]))
            ->action(__('admin.tenants.actions.view_profile'), TenantResource::getUrl('view', [
                'record' => $this->tenant,
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
            'type' => 'tenant_invitation_accepted',
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
            'tenant_email' => $this->tenant->email,
            'invitation_id' => $this->invitation->id,
        ];
    }
}
