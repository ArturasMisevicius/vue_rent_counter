<?php

declare(strict_types=1);

namespace App\Notifications\Projects;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ProjectEmergencyCreatedNotification extends Notification
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
            ->subject('Emergency project created')
            ->greeting('Emergency project created')
            ->line("{$this->project->name} was created directly in progress.")
            ->line('Reference: '.($this->project->reference_number ?? '—'));
    }
}
