<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrganizationOwnerInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Organization $organization,
        private readonly string $temporaryPassword,
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
            ->subject("You're invited to manage {$this->organization->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A new organization account has been created for {$this->organization->name}.")
            ->line('You have been assigned as the owner account for this organization.')
            ->line("Login email: {$notifiable->email}")
            ->line("Temporary password: {$this->temporaryPassword}")
            ->line('Please sign in and change your password after your first login.')
            ->action('Go to Login', route('login'))
            ->line('If you were not expecting this invitation, please contact the platform administrator.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'organization_id' => $this->organization->id,
            'organization_name' => $this->organization->name,
            'email' => $notifiable->email,
        ];
    }
}
