<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use App\Models\MeterReading;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ReadingRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly MeterReading $reading,
        private readonly string $comment,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('admin.billing_review.notifications.reading_rejected.subject'))
            ->line(__('admin.billing_review.notifications.reading_rejected.body', [
                'meter' => $this->meterName(),
            ]))
            ->line($this->comment);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('admin.billing_review.notifications.reading_rejected.title'),
            'body' => __('admin.billing_review.notifications.reading_rejected.body', [
                'meter' => $this->meterName(),
            ]),
            'comment' => $this->comment,
            'reading_id' => $this->reading->id,
            'meter_id' => $this->reading->meter_id,
            'organization_id' => $this->reading->organization_id,
            'property_id' => $this->reading->property_id,
        ];
    }

    private function meterName(): string
    {
        $this->reading->loadMissing('meter:id,name,type');

        return $this->reading->meter?->displayName() ?? __('admin.billing_review.unknown_meter');
    }
}
