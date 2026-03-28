<?php

declare(strict_types=1);

namespace App\Notifications\Superadmin;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrganizationDataExportReadyNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Organization $organization,
        public readonly string $exportPath,
        public readonly string $reason,
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
            ->subject(__('superadmin.organizations.mail.export_ready_subject', [
                'name' => $this->organization->name,
            ]))
            ->greeting(__('superadmin.organizations.mail.export_ready_greeting'))
            ->line(__('superadmin.organizations.mail.export_ready_intro', [
                'name' => $this->organization->name,
            ]))
            ->line(__('superadmin.organizations.mail.export_ready_reason', [
                'reason' => $this->reason,
            ]))
            ->attach($this->exportPath, [
                'as' => "{$this->organization->slug}-organization-export.zip",
                'mime' => 'application/zip',
            ]);
    }
}
