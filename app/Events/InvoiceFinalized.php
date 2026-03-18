<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class InvoiceFinalized implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $organizationId,
        public readonly int $invoiceId,
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
        return 'invoice.finalized';
    }

    /**
     * @return array{organization_id: int, invoice_id: int, tenant_user_id: int|null}
     */
    public function broadcastWith(): array
    {
        return [
            'organization_id' => $this->organizationId,
            'invoice_id' => $this->invoiceId,
            'tenant_user_id' => $this->tenantUserId,
        ];
    }
}
