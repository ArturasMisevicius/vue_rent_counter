<?php

declare(strict_types=1);

namespace App\Notifications\Projects;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ProjectCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Project $project,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Project cancelled')
            ->greeting('Project cancelled')
            ->line("{$this->project->name} has been cancelled.")
            ->line('Reason: '.($this->project->cancellation_reason ?? '—'));
    }
}
