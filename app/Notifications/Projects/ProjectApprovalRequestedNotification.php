<?php

declare(strict_types=1);

namespace App\Notifications\Projects;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ProjectApprovalRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Project $project,
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
            ->subject('Project approval requested')
            ->greeting('Project approval requested')
            ->line("{$this->project->name} requires approval before work can start.")
            ->line('Reference: '.($this->project->reference_number ?? '—'));
    }
}
