<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

/**
 * Security Alert Notification
 * 
 * Sends alerts for security violations
 */
final class SecurityAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $violationType,
        private readonly int $count,
    ) {}

    /**
     * Get the notification's delivery channels
     */
    public function via(mixed $notifiable): array
    {
        return ['slack', 'mail'];
    }

    /**
     * Get the Slack representation of the notification
     */
    public function toSlack(mixed $notifiable): SlackMessage
    {
        $severity = $this->getSeverity();
        $emoji = $this->getEmoji($severity);
        
        return (new SlackMessage())
            ->error()
            ->content("{$emoji} **SECURITY ALERT** - {$this->violationType}")
            ->attachment(function ($attachment) use ($severity) {
                $attachment
                    ->title("Security Violation Detected")
                    ->fields([
                        'Type' => $this->violationType,
                        'Count' => $this->count,
                        'Severity' => strtoupper($severity),
                        'Time' => now()->format('Y-m-d H:i:s T'),
                        'Environment' => app()->environment(),
                    ])
                    ->color($severity === 'critical' ? 'danger' : 'warning');
            });
    }

    /**
     * Get the mail representation of the notification
     */
    public function toMail(mixed $notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        $severity = $this->getSeverity();
        
        return (new \Illuminate\Notifications\Messages\MailMessage())
            ->subject("Security Alert: {$this->violationType}")
            ->greeting("Security Violation Detected")
            ->line("A security violation has been detected in the application.")
            ->line("**Type:** {$this->violationType}")
            ->line("**Count:** {$this->count}")
            ->line("**Severity:** " . strtoupper($severity))
            ->line("**Environment:** " . app()->environment())
            ->line("**Time:** " . now()->format('Y-m-d H:i:s T'))
            ->action('View Security Dashboard', url('/admin/security'))
            ->line('Please investigate this issue immediately.');
    }

    /**
     * Get severity level based on violation type
     */
    private function getSeverity(): string
    {
        return match ($this->violationType) {
            'policy_registration_failure', 'unauthorized_access' => 'critical',
            'suspicious_requests', 'rate_limit_exceeded' => 'high',
            default => 'medium',
        };
    }

    /**
     * Get emoji for severity level
     */
    private function getEmoji(string $severity): string
    {
        return match ($severity) {
            'critical' => '🚨',
            'high' => '⚠️',
            'medium' => '⚡',
            default => 'ℹ️',
        };
    }
}