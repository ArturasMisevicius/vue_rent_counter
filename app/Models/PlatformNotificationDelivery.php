<?php

namespace App\Models;

use Database\Factories\PlatformNotificationDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformNotificationDelivery extends Model
{
    /** @use HasFactory<PlatformNotificationDeliveryFactory> */
    use HasFactory;

    protected $fillable = [
        'platform_notification_id',
        'user_id',
        'channel',
        'delivered_at',
        'failed_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(PlatformNotification::class, 'platform_notification_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
