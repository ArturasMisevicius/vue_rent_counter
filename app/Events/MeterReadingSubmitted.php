<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MeterReadingSubmitted implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $organizationId,
        public readonly int $meterReadingId,
        public readonly int $meterId,
        public readonly int $propertyId,
        public readonly ?int $tenantUserId = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('org.'.$this->organizationId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'reading.submitted';
    }

    /**
     * @return array{organization_id: int, meter_reading_id: int, meter_id: int, property_id: int, tenant_user_id: int|null}
     */
    public function broadcastWith(): array
    {
        return [
            'organization_id' => $this->organizationId,
            'meter_reading_id' => $this->meterReadingId,
            'meter_id' => $this->meterId,
            'property_id' => $this->propertyId,
            'tenant_user_id' => $this->tenantUserId,
        ];
    }
}
