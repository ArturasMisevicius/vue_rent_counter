<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Compliance Issue Notification
 * 
 * Notifies administrators when compliance scores drop below acceptable thresholds.
 */
final class ComplianceIssueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $tenantId,
        private readonly float $overallScore,
        private readonly array $failingCategories,
        private readonly array $recommendations,
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(mixed $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('audit.notifications.compliance_issue.subject', [
                'score' => number_format($this->overallScore, 1),
            ]))
            ->greeting(__('audit.notifications.compliance_issue.greeting'))
            ->line(__('audit.notifications.compliance_issue.intro', [
                'score' => number_format($this->overallScore, 1),
                'tenant_id' => $this->tenantId,
            ]));

        // Add failing categories
        if (!empty($this->failingCategories)) {
            $message->line(__('audit.notifications.compliance_issue.failing_categories'));
            
            foreach ($this->failingCategories as $category) {
                $categoryName = $this->getCategoryLabel($category['category']);
                $score = number_format($category['score'], 1);
                $message->line("• {$categoryName}: {$score}%");
            }
        }

        // Add recommendations
        if (!empty($this->recommendations)) {
            $message->line(__('audit.notifications.compliance_issue.recommendations'));
            
            foreach (array_slice($this->recommendations, 0, 5) as $recommendation) {
                $message->line("• {$recommendation}");
            }
        }

        $message->action(
            __('audit.notifications.compliance_issue.action'),
            route('filament.tenant.pages.dashboard')
        );

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'overall_score' => $this->overallScore,
            'failing_categories' => $this->failingCategories,
            'recommendations' => $this->recommendations,
            'title' => __('audit.notifications.compliance_issue.title'),
            'message' => __('audit.notifications.compliance_issue.summary', [
                'score' => number_format($this->overallScore, 1),
            ]),
            'icon' => 'heroicon-o-shield-exclamation',
            'color' => $this->getScoreColor(),
        ];
    }

    /**
     * Get human-readable category label.
     */
    private function getCategoryLabel(string $category): string
    {
        return match ($category) {
            'audit_trail' => __('audit.compliance_categories.audit_trail'),
            'data_retention' => __('audit.compliance_categories.data_retention'),
            'regulatory' => __('audit.compliance_categories.regulatory'),
            'security' => __('audit.compliance_categories.security'),
            'data_quality' => __('audit.compliance_categories.data_quality'),
            default => ucfirst(str_replace('_', ' ', $category)),
        };
    }

    /**
     * Get color based on compliance score.
     */
    private function getScoreColor(): string
    {
        return match (true) {
            $this->overallScore >= 90 => 'success',
            $this->overallScore >= 80 => 'warning',
            $this->overallScore >= 70 => 'danger',
            default => 'gray',
        };
    }
}