<?php

declare(strict_types=1);

namespace App\Filament\Support\Notifications;

final readonly class DomainNotificationContent
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $type,
        public string $title,
        public string $message,
        public ?string $actionUrl = null,
        public array $data = [],
        public ?string $dedupeKey = null,
        public bool $sendEmail = false,
        public ?string $emailSubject = null,
        public ?string $emailGreeting = null,
        public ?string $emailActionLabel = null,
    ) {}

    public function subject(): string
    {
        return $this->emailSubject ?: $this->title;
    }

    public function greeting(): string
    {
        return $this->emailGreeting ?: __('notifications.mail.greeting');
    }

    public function actionLabel(): string
    {
        return $this->emailActionLabel ?: __('notifications.mail.action');
    }
}
