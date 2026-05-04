<?php

declare(strict_types=1);

namespace App\Notifications\Projects;

use App\Filament\Support\Formatting\EuMoneyFormatter;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ProjectOverBudgetNotification extends Notification
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
            ->subject('Project over budget')
            ->greeting('Project budget alert')
            ->line("{$this->project->name} is over budget.")
            ->line('Budget: '.EuMoneyFormatter::format($this->project->budget_amount ?? 0))
            ->line('Actual: '.EuMoneyFormatter::format($this->project->actual_cost));
    }
}
