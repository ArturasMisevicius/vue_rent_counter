<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PlatformNotificationSent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $organizationId,
        public readonly int $notificationId,
        public readonly int $recipientId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('org.'.$this->organizationId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'platform-notification.sent';
    }

    /**
     * @return array{organization_id: int, notification_id: int, recipient_id: int}
     */
    public function broadcastWith(): array
    {
        return [
            'organization_id' => $this->organizationId,
            'notification_id' => $this->notificationId,
            'recipient_id' => $this->recipientId,
        ];
    }
}
