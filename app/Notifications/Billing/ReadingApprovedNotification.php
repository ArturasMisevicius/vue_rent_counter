<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use App\Models\MeterReading;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ReadingApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly MeterReading $reading,
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
            ->subject(__('admin.billing_review.notifications.reading_approved.subject'))
            ->line(__('admin.billing_review.notifications.reading_approved.body', [
                'meter' => $this->meterName(),
                'value' => $this->reading->reading_value,
            ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('admin.billing_review.notifications.reading_approved.title'),
            'body' => __('admin.billing_review.notifications.reading_approved.body', [
                'meter' => $this->meterName(),
                'value' => $this->reading->reading_value,
            ]),
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
