<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a user's subscription cache is warmed.
 * 
 * This event can be used for monitoring cache warming operations
 * and tracking cache performance metrics.
 * 
 * @package App\Events
 */
class SubscriptionCacheWarmed
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param User $user The user whose cache was warmed
     */
    public function __construct(
        public readonly User $user
    ) {
    }
}
